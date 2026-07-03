<?php

declare(strict_types=1);

namespace PHPAdmin\Core;

use PHPAdmin\Core\Exceptions\AppException;
use PHPAdmin\Core\Exceptions\ForbiddenAppException;
use PHPAdmin\Core\Exceptions\UnauthorizedAppException;
use PHPAdmin\Core\Exceptions\ValidationAppException;

/**
 * Central error handler for the application.
 */
class ErrorHandler
{
    /**
     * Handle an AppException and emit the appropriate response.
     *
     * @param array<string, mixed> $flash Previously-read flash data (not used for redirect/json responses).
     */
    public static function handle(
        AppException $e,
        string $uri,
        AppConfig $config,
        array $flash = []
    ): void {
        $isApi = str_starts_with($uri, '/api/');

        if ($isApi) {
            self::handleApiError($e);
        } else {
            self::handleWebError($e, $uri, $config);
        }
    }

    private static function handleApiError(AppException $e): never
    {
        $payload = [
            'status'  => false,
            'message' => $e->getMessage(),
            'data'    => null,
        ];

        if ($e instanceof ValidationAppException) {
            $payload['errors'] = $e->getErrors();
        }

        json_response($payload, $e->getCode());
    }

    private static function handleWebError(AppException $e, string $uri, AppConfig $config): never
    {
        if ($e instanceof UnauthorizedAppException) {
            redirect('/auth/login');
        }

        if ($e instanceof ForbiddenAppException) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['flash'] = ['error' => 'Unauthorized.'];
            }
            $ref = (string)($_SERVER['HTTP_REFERER'] ?? '/admin/v1/dashboard');
            redirect($ref);
        }

        if ($e instanceof ValidationAppException) {
            $_SESSION['_errors']    = $e->getErrors();
            $_SESSION['_old_input'] = $_POST;
            $back = $_SERVER['HTTP_REFERER'] ?? '/admin/v1/dashboard';
            redirect($back);
        }

        // Generic error — render error view
        $code     = $e->getCode();
        $viewFile = (string)$code;

        $allowedCodes = ['400', '404', '405', '409', '419', '422', '500'];
        if (!in_array($viewFile, $allowedCodes, true)) {
            $viewFile = '500';
        }

        $viewPath = $config->appRoot . '/src/views/errors/' . $viewFile . '.php';

        if (!file_exists($viewPath)) {
            $viewPath = $config->appRoot . '/src/views/errors/500.php';
        }

        http_response_code((int)$viewFile);
        render($viewPath, [
            'code'    => (int)$viewFile,
            'message' => $e->getMessage(),
            'config'  => $config,
        ]);
        exit;
    }
}
