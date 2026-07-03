<?php

declare(strict_types=1);

namespace PHPAdmin\Core\Middleware;

use PHPAdmin\Core\RouteRegistry;
use PHPAdmin\Core\Exceptions\ForbiddenAppException;

/**
 * Authorization middleware.
 *
 * Checks that the currently authenticated user has access to the requested route.
 * Administrator role bypasses all permission checks.
 */
class AuthorizeMiddleware
{
    /**
     * Run the authorization check.
     *
     * @param object $user  The authenticated user model (must expose roles + hasAccess()).
     * @param string $method HTTP method (e.g. 'GET', 'POST').
     * @param string $uri   The request URI path.
     *
     * @throws ForbiddenAppException
     */
    public static function run(object $user, string $method, string $uri): bool
    {
        // Administrator role bypasses everything
        if (self::isAdministrator($user)) {
            return true;
        }

        $routeName = RouteRegistry::getInstance()->getNameByPathAndMethod($uri, $method);

        // If the route has no name registered, allow by default
        // (unregistered routes are typically public or handled elsewhere)
        if ($routeName === null) {
            return true;
        }

        if (!$user->hasAccess($routeName, $method)) {
            throw new ForbiddenAppException(
                'You do not have permission to perform this action.'
            );
        }

        return true;
    }

    /**
     * Check whether a user has the Administrator role.
     */
    private static function isAdministrator(object $user): bool
    {
        // Support both Eloquent collections and plain arrays
        $roles = $user->roles ?? [];

        if (is_iterable($roles)) {
            foreach ($roles as $role) {
                $name = is_object($role) ? ($role->name ?? '') : ($role['name'] ?? '');
                if (strtolower((string)$name) === 'administrator') {
                    return true;
                }
            }
        }

        return false;
    }
}
