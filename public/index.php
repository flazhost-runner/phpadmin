<?php

declare(strict_types=1);

// ─── 0. php -S: serve static files directly (router passthrough) ─────────────
// Emulates what a real web server (nginx/apache with docroot = public/) does for
// the built-in dev server: serve existing files under public/ — including the
// `public/storage → ../storage` symlink used by the local STORAGE_DRIVER — before
// the router runs. In production this block never executes (php-fpm ≠ cli-server);
// the web server serves static assets itself. Must run before ob_start().
//
// Path-traversal guard: the requested URL is url-decoded and resolved with
// realpath(), then confined to an allow-list of real base directories (the public
// dir and the storage dir it symlinks to). A request like /storage/../../.env
// resolves outside both roots and is rejected — it falls through to the router
// (→ 404) instead of leaking files.
if (PHP_SAPI === 'cli-server') {
    $reqPath    = rawurldecode(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
    $realFile   = realpath(__DIR__ . $reqPath);
    $allowRoots = array_filter([realpath(__DIR__), realpath(__DIR__ . '/storage')]);

    $withinRoot = false;
    if ($realFile !== false && $realFile !== __FILE__) {
        foreach ($allowRoots as $root) {
            if ($realFile === $root || str_starts_with($realFile, $root . DIRECTORY_SEPARATOR)) {
                $withinRoot = true;
                break;
            }
        }
    }

    if ($withinRoot && is_file($realFile)) {
        static $mimes = [
            'css' => 'text/css', 'js' => 'application/javascript',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject', 'map' => 'application/json',
            'json' => 'application/json', 'webp' => 'image/webp',
        ];
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile($realFile);
        exit;
    }
}

// ─── 1. Output buffering — must be first ────────────────────────────────────
ob_start();

// ─── 2. App root constant ────────────────────────────────────────────────────
define('APP_ROOT', dirname(__DIR__));

// ─── 3. Autoloader ──────────────────────────────────────────────────────────
require APP_ROOT . '/vendor/autoload.php';

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\Database;
use PHPAdmin\Core\ErrorHandler;
use PHPAdmin\Core\Exceptions\AppException;
use PHPAdmin\Core\Exceptions\NotFoundAppException;
use PHPAdmin\Core\Middleware\CsrfMiddleware;
use PHPAdmin\Core\DatabaseSessionHandler;
use PHPAdmin\Core\RedisSessionHandler;
use PHPAdmin\Core\RouteRegistry;
use Predis\Client as PredisClient;

// ─── 4. Load .env ────────────────────────────────────────────────────────────
$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

// ─── 5. Application config ───────────────────────────────────────────────────
try {
    $config = new AppConfig();
} catch (\Throwable $e) {
    http_response_code(500);
    echo '<h1>500 – Configuration Error</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}

// ─── 6. Timezone ─────────────────────────────────────────────────────────────
date_default_timezone_set($config->tz);

// ─── 7. Database ─────────────────────────────────────────────────────────────
try {
    Database::initialize($config);
} catch (\Throwable $e) {
    http_response_code(500);
    echo '<h1>500 – Database Error</h1>';
    if (!$config->isProduction()) {
        echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    }
    exit;
}

// ─── 8. Session setup (full mode only) ───────────────────────────────────────
if ($config->isFullMode()) {
    // SESSION_DRIVER=redis  : simpan sesi di Redis (hilang saat Redis restart).
    // SESSION_DRIVER=database: simpan sesi di tabel `sessions` DB utama (persist).
    $ttlSec = $config->sessionTtlHours * 3600;
    if ($config->sessionDriver === 'database') {
        try {
            $sessionHandler = new DatabaseSessionHandler(Database::pdo(), $ttlSec);
            session_set_save_handler($sessionHandler, true);
        } catch (\Throwable) {
            // Fall back to native session handler silently
        }
    } elseif ($config->redisHost !== '') {
        try {
            // redisParameters() adds TLS + SNI (peer_name) when REDIS_URL is rediss://.
            $redis          = new PredisClient($config->redisParameters());
            $sessionHandler = new RedisSessionHandler($redis, $ttlSec);
            session_set_save_handler($sessionHandler, true);
        } catch (\Throwable) {
            // Fall back to native session handler silently
        }
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $config->isProduction(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('PHPADMIN_SESSION');
    session_start();

    // Initialise CSRF token
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
}

// ─── 9. PHP-DI container ─────────────────────────────────────────────────────
$definitions = require APP_ROOT . '/config/definitions.php';
$container   = (new DI\ContainerBuilder())
    ->addDefinitions($definitions)
    ->build();

// Make AppConfig available to any DI consumer that has not been pre-bound
$container->set(AppConfig::class, $config);

// ─── 10. Method override (POST → PUT/PATCH/DELETE) ────────────────────────────
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'POST') {
    $override = strtoupper(
        (string)($_POST['_method'] ?? $_GET['_method'] ?? '')
    );
    if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        $method = $override;
    }
}

// ─── 11. Security headers ────────────────────────────────────────────────────
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ─── 12. Load modules & build FastRoute dispatcher ───────────────────────────
$moduleClasses = require APP_ROOT . '/config/modules.php';
$registry      = RouteRegistry::getInstance();

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) use ($moduleClasses, $registry, $container): void {
    foreach ($moduleClasses as $moduleClass) {
        /** @var object $module */
        $module = $container->make($moduleClass);
        if (method_exists($module, 'register')) {
            $module->register($r, $registry);
        }
    }
});

// ─── 13. Dispatch ────────────────────────────────────────────────────────────
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$routeInfo = $dispatcher->dispatch($method, $uri);

$flash = [];

switch ($routeInfo[0]) {
    // ── 404 Not Found ────────────────────────────────────────────────────────
    case Dispatcher::NOT_FOUND:
        try {
            throw new NotFoundAppException("The page '{$uri}' was not found.");
        } catch (AppException $e) {
            ErrorHandler::handle($e, $uri, $config, []);
        }
        break;

    // ── 405 Method Not Allowed ────────────────────────────────────────────────
    case Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        $viewPath = APP_ROOT . '/src/views/errors/405.php';
        if (file_exists($viewPath)) {
            render($viewPath, [
                'code'    => 405,
                'message' => 'Method Not Allowed',
                'allowed' => implode(', ', $routeInfo[1]),
                'config'  => $config,
            ]);
        } else {
            echo '<h1>405 – Method Not Allowed</h1>';
        }
        break;

    // ── Found ─────────────────────────────────────────────────────────────────
    case Dispatcher::FOUND:
        // Read and clear flash data
        if ($config->isFullMode() && isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }
        // Clear per-request validation state (set by previous redirects)
        $errors   = $_SESSION['_errors']    ?? [];
        $oldInput = $_SESSION['_old_input'] ?? [];
        unset($_SESSION['_errors'], $_SESSION['_old_input']);

        // CSRF check (full mode, non-API, mutating methods)
        if ($config->isFullMode()) {
            CsrfMiddleware::run();
        }

        $handler    = $routeInfo[1];
        $routeVars  = $routeInfo[2];

        try {
            // Merge route vars into _GET for convenient access in handlers
            $_GET = array_merge($_GET, $routeVars);

            if (is_callable($handler)) {
                $handler($container, $routeVars);
            } elseif (is_array($handler) && count($handler) === 2) {
                [$controllerClass, $handlerMethod] = $handler;
                $ctrl = $container->get($controllerClass);
                $ctrl->{$handlerMethod}($routeVars, $flash, $errors, $oldInput);
            }
        } catch (AppException $e) {
            ErrorHandler::handle($e, $uri, $config, $flash);
        } catch (\Throwable $e) {
            http_response_code(500);
            $viewPath = APP_ROOT . '/src/views/errors/500.php';
            if (!$config->isProduction()) {
                $message = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            } else {
                $message = 'An unexpected error occurred. Please try again later.';
            }
            if (file_exists($viewPath)) {
                render($viewPath, ['code' => 500, 'message' => $message, 'config' => $config]);
            } else {
                echo '<h1>500 – Internal Server Error</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }
        break;
}
