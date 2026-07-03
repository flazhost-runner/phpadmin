<?php

declare(strict_types=1);

namespace PHPAdmin\Core\Middleware;

/**
 * CSRF protection middleware.
 *
 * Skips safe methods (GET, HEAD, OPTIONS) and /api/ paths.
 * Validates the CSRF token using constant-time comparison.
 */
class CsrfMiddleware
{
    /** @var list<string> HTTP methods exempt from CSRF check */
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Run the middleware.
     * Returns true if the request is allowed; exits with 419 on failure.
     */
    public static function run(): bool
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        // Skip safe methods
        if (in_array($method, self::SAFE_METHODS, true)) {
            return true;
        }

        // Skip API routes (they use JWT/bearer tokens, not CSRF)
        if (str_starts_with($uri, '/api/')) {
            return true;
        }

        $sessionToken = $_SESSION['_csrf'] ?? '';

        $requestToken = $_POST['_csrf']
            ?? $_GET['_csrf']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';

        if (
            $sessionToken === ''
            || !is_string($requestToken)
            || !hash_equals($sessionToken, $requestToken)
        ) {
            http_response_code(419);
            $viewPath = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3))
                . '/src/views/errors/419.php';

            if (file_exists($viewPath)) {
                include $viewPath;
            } else {
                echo '<h1>419 – CSRF token mismatch</h1>';
            }
            exit;
        }

        return true;
    }
}
