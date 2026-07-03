<?php

declare(strict_types=1);

namespace PHPAdmin\Core\Middleware;

/**
 * Authentication middleware.
 *
 * Checks for an authenticated session user.
 * Web requests are redirected to the login page; API requests get a 401 JSON response.
 */
class AuthMiddleware
{
    /**
     * Run the authentication check.
     *
     * @param string $uri The current request URI (used to distinguish API vs web).
     */
    public static function run(string $uri): bool
    {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId !== null) {
            return true;
        }

        $isApi = str_starts_with($uri, '/api/');

        if ($isApi) {
            json_response(['status' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

        redirect('/auth/login');
    }
}
