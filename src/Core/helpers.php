<?php

declare(strict_types=1);

use PHPAdmin\Core\RouteRegistry;

if (!function_exists('e')) {
    /**
     * Escape a value for safe HTML output.
     */
    function e(mixed $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('route')) {
    /**
     * Generate a URL for a named route.
     *
     * @param array<string, string> $params
     */
    function route(string $name, array $params = []): string
    {
        return RouteRegistry::getInstance()->url($name, $params);
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Render a hidden CSRF input field.
     */
    function csrf_field(): string
    {
        $token = $_SESSION['_csrf'] ?? '';
        return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
    }
}

if (!function_exists('old')) {
    /**
     * Get old (flashed) input value.
     */
    function old(string $key, mixed $default = ''): string
    {
        $old = $_SESSION['_old_input'][$key] ?? $default;
        return e($old);
    }
}

if (!function_exists('has_error')) {
    /**
     * Check if a field has a validation error.
     */
    function has_error(string $field): bool
    {
        return isset($_SESSION['_errors'][$field]);
    }
}

if (!function_exists('get_error')) {
    /**
     * Get the validation error message for a field.
     */
    function get_error(string $field): string
    {
        return e($_SESSION['_errors'][$field] ?? '');
    }
}

if (!function_exists('flash_success')) {
    /**
     * Flash a success message to the session.
     */
    function flash_success(string $message): void
    {
        $_SESSION['flash']['success'] = $message;
    }
}

if (!function_exists('flash_error')) {
    /**
     * Flash an error message to the session.
     */
    function flash_error(string $message): void
    {
        $_SESSION['flash']['error'] = $message;
    }
}

if (!function_exists('paginate')) {
    /**
     * Create a standard NodeAdmin-shape pagination array.
     *
     * @return array{
     *     datas: array<mixed>,
     *     paginate_data: array{total_data: int, page_size: int, current_page: int, total_page: int}
     * }
     */
    function paginate(array $items, int $total, int $page, int $perPage): array
    {
        return [
            'datas'        => $items,
            'paginate_data' => [
                'total_data'   => $total,
                'page_size'    => $perPage,
                'current_page' => $page,
                'total_page'   => (int)ceil($total / max(1, $perPage)),
            ],
        ];
    }
}

if (!function_exists('getFile')) {
    /**
     * Generate a web-accessible URL for a stored file path.
     *
     * If the path is already an absolute URL (http/https) it is returned as-is.
     * Otherwise prepend '/' to make it relative to the public root.
     * When STORAGE_BASE_URL is set and the path does not look like a local
     * uploads/ path, the base URL is used instead.
     */
    function getFile(string $path): string
    {
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        // If a STORAGE_BASE_URL is configured and the path is NOT a local
        // uploads/ path (i.e. it's a cloud object key), prefix with base URL.
        $baseUrl = rtrim($_ENV['STORAGE_BASE_URL'] ?? '', '/');
        if ($baseUrl !== '' && !str_starts_with($path, 'uploads/')) {
            return $baseUrl . '/' . ltrim($path, '/');
        }
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('ci_like')) {
    /**
     * Build a case-insensitive LIKE pattern (wraps value with % on both sides).
     */
    function ci_like(string $value): string
    {
        return '%' . str_replace(['%', '_'], ['\%', '\_'], $value) . '%';
    }
}

if (!function_exists('render')) {
    /**
     * Render a PHP view file with extracted variables.
     *
     * @param array<string, mixed> $data
     */
    function render(string $viewPath, array $data = []): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        ob_start();
        extract($data, EXTR_SKIP);
        include $viewPath;
        $content = ob_get_clean();
        echo $content;
    }
}

if (!function_exists('redirect')) {
    /**
     * Send a redirect response and exit.
     */
    function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . $url, true, $status);
        exit;
    }
}

if (!function_exists('json_response')) {
    /**
     * Send a JSON response and exit.
     *
     * @param array<string, mixed> $data
     */
    function json_response(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('require_api_auth')) {
    /**
     * Validate the Bearer token from Authorization header and return the JWT payload.
     * Aborts with 401 JSON response if token is missing or invalid.
     *
     * @return array<string,mixed>
     */
    function require_api_auth(): array
    {
        $authHeader = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $token      = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : '';
        if ($token === '') {
            json_response(['status' => false, 'message' => 'Unauthorized', 'data' => null], 401);
            exit;
        }
        try {
            $secret  = (string)($_ENV['JWT_SECRET'] ?? '');
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));
            $payload = (array)$decoded;
            // Check blacklist if Redis is available
            $jti = (string)($payload['jti'] ?? '');
            if ($jti !== '' && class_exists(\PHPAdmin\Core\RedisSessionHandler::class)) {
                $redisHost = (string)($_ENV['REDIS_HOST'] ?? '');
                if ($redisHost !== '') {
                    try {
                        $redis = new \Predis\Client(['scheme' => 'tcp', 'host' => $redisHost,
                            'port' => (int)($_ENV['REDIS_PORT'] ?? 6379)]);
                        if ($redis->exists("jwt_blacklist:{$jti}")) {
                            json_response(['status' => false, 'message' => 'Token revoked', 'data' => null], 401);
                            exit;
                        }
                    } catch (\Throwable) {
                    }
                }
            }
            return $payload;
        } catch (\Throwable) {
            json_response(['status' => false, 'message' => 'Invalid or expired token', 'data' => null], 401);
            exit;
        }
    }
}

if (!function_exists('uuid')) {
    /**
     * Generate a RFC4122 v4 UUID.
     */
    function uuid(): string
    {
        $bytes = random_bytes(16);
        // Set version to 4
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        // Set variant to 10xxxxxx
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(substr($bytes, 0, 4)),
            bin2hex(substr($bytes, 4, 2)),
            bin2hex(substr($bytes, 6, 2)),
            bin2hex(substr($bytes, 8, 2)),
            bin2hex(substr($bytes, 10, 6))
        );
    }
}
