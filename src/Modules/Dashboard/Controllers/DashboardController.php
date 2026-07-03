<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Dashboard\Controllers;

use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\Middleware\AuthMiddleware;
use PHPAdmin\Core\Middleware\AuthorizeMiddleware;
use PHPAdmin\Core\SettingCache;
use PHPAdmin\Core\Themes;
use PHPAdmin\Modules\Access\Models\User;
use PHPAdmin\Modules\Dashboard\Contracts\IDashboardService;

/**
 * DashboardController — thin controller; delegates all data fetching to the service.
 *
 * Method signature: ($routeVars, $flash, $errors, $oldInput).
 */
class DashboardController
{
    public function __construct(
        private readonly IDashboardService $dashboardService,
        private readonly AppConfig $config
    ) {
    }

    // ─── Web ──────────────────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function index(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $user = $this->requireAuth();

        $stats      = $this->dashboardService->getStats();
        $activities = $this->dashboardService->getRecentActivities();

        $this->renderAdmin('index.php', [
            'flash'      => $flash,
            'stats'      => $stats,
            'activities' => $activities,
            'authUser'   => $user,
        ], 'Dashboard');
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Enforce authentication + authorization; return the current user model.
     */
    private function requireAuth(): User
    {
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        AuthMiddleware::run($uri);

        $userId = (string)($_SESSION['user_id'] ?? '');
        /** @var User|null $user */
        $user = User::with('roles.permissions')->find($userId);

        if ($user === null) {
            redirect(route('web.auth.login'));
        }

        AuthorizeMiddleware::run($user, $method, $uri);

        return $user;
    }

    /**
     * Buffer a dashboard view and render it inside the admin_main layout.
     *
     * @param array<string,mixed> $data
     */
    private function renderAdmin(string $viewFile, array $data = [], string $title = 'PHPAdmin'): void
    {
        $setting   = SettingCache::get() ?? [];
        $themeName = (string)($setting['theme'] ?? 'Blue');

        try {
            $theme = Themes::get($themeName);
        } catch (\InvalidArgumentException) {
            $theme     = Themes::get('Blue');
            $themeName = 'Blue';
        }

        $currentUser = $data['authUser'] ?? null;

        $pageData = array_merge([
            'theme'       => $theme,
            'setting'     => $setting,
            'themeName'   => $themeName,
            'themes'      => Themes::all(),
            '_csrf'       => (string)($_SESSION['_csrf'] ?? ''),
            'currentUser' => $currentUser,
            'flash'       => [],
            'errors'      => [],
            'oldInput'    => [],
            'pageTitle'   => $title . ' — ' . $this->config->appName,
        ], $data);

        ob_start();
        extract($pageData, EXTR_SKIP);
        include $this->config->appRoot . '/src/views/dashboard/' . $viewFile;
        $content = ob_get_clean();

        render(
            $this->config->appRoot . '/src/views/layouts/admin_main.php',
            array_merge($pageData, ['content' => $content])
        );
    }
}
