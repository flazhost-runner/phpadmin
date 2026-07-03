<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Auth;

use FastRoute\RouteCollector;
use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\RouteRegistry;
use PHPAdmin\Modules\Auth\Controllers\AuthController;

/**
 * AuthModule — registers all authentication routes (web + API).
 *
 * Web routes are only registered in full mode (session-based UI).
 * API routes are always registered regardless of APP_MODE.
 *
 * Add this class to config/modules.php to activate:
 *   PHPAdmin\Modules\Auth\AuthModule::class,
 */
class AuthModule
{
    public function __construct(
        private readonly AppConfig $config
    ) {
    }

    /**
     * Register routes into FastRoute's RouteCollector and the named RouteRegistry.
     */
    public function register(RouteCollector $r, RouteRegistry $registry): void
    {
        // ─── Web routes (session/UI — full mode only) ─────────────────────────
        if ($this->config->isFullMode()) {
            // Login
            $r->addRoute('GET', '/auth/login', [AuthController::class, 'showLogin']);
            $registry->register('web.auth.login', 'GET', '/auth/login');

            $r->addRoute('POST', '/auth/login', [AuthController::class, 'login']);
            $registry->register('web.auth.login.post', 'POST', '/auth/login');

            // Register
            $r->addRoute('GET', '/auth/register', [AuthController::class, 'showRegister']);
            $registry->register('web.auth.register', 'GET', '/auth/register');

            $r->addRoute('POST', '/auth/register', [AuthController::class, 'register']);
            $registry->register('web.auth.register.post', 'POST', '/auth/register');

            // Logout
            $r->addRoute('POST', '/auth/logout', [AuthController::class, 'logout']);
            $registry->register('web.auth.logout', 'POST', '/auth/logout');

            // Password reset — request OTP
            $r->addRoute('GET', '/admin/v1/auth/reset/req', [AuthController::class, 'showResetReq']);
            $registry->register('admin.v1.auth.reset.req', 'GET', '/admin/v1/auth/reset/req');

            $r->addRoute('POST', '/admin/v1/auth/reset/request', [AuthController::class, 'resetRequest']);
            $registry->register('admin.v1.auth.reset.request', 'POST', '/admin/v1/auth/reset/request');

            // Password reset — process OTP
            $r->addRoute('GET', '/admin/v1/auth/reset/proc', [AuthController::class, 'showResetProc']);
            $registry->register('admin.v1.auth.reset.proc', 'GET', '/admin/v1/auth/reset/proc');

            $r->addRoute('POST', '/admin/v1/auth/reset/process', [AuthController::class, 'resetProcess']);
            $registry->register('admin.v1.auth.reset.process', 'POST', '/admin/v1/auth/reset/process');
        }

        // ─── API routes (always active) ───────────────────────────────────────

        // Login
        $r->addRoute('POST', '/api/v1/auth/login', [AuthController::class, 'apiLogin']);
        $registry->register('api.v1.auth.login', 'POST', '/api/v1/auth/login');

        // Logout
        $r->addRoute('POST', '/api/v1/auth/logout', [AuthController::class, 'apiLogout']);
        $registry->register('api.v1.auth.logout', 'POST', '/api/v1/auth/logout');

        // Me (current user)
        $r->addRoute('GET', '/api/v1/auth/me', [AuthController::class, 'apiMe']);
        $registry->register('api.v1.auth.me', 'GET', '/api/v1/auth/me');

        // Register
        $r->addRoute('POST', '/api/v1/auth/register', [AuthController::class, 'apiRegister']);
        $registry->register('api.v1.auth.register', 'POST', '/api/v1/auth/register');

        // Password reset — request OTP
        $r->addRoute('POST', '/api/v1/auth/reset/request', [AuthController::class, 'apiResetRequest']);
        $registry->register('api.v1.auth.reset.request', 'POST', '/api/v1/auth/reset/request');

        // Password reset — process OTP
        $r->addRoute('POST', '/api/v1/auth/reset/process', [AuthController::class, 'apiResetProcess']);
        $registry->register('api.v1.auth.reset.process', 'POST', '/api/v1/auth/reset/process');
    }
}
