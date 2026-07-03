<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Dashboard;

use FastRoute\RouteCollector;
use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\RouteRegistry;
use PHPAdmin\Modules\Dashboard\Controllers\DashboardController;

/**
 * DashboardModule — registers the admin dashboard route.
 *
 * Activate in config/modules.php:
 *   PHPAdmin\Modules\Dashboard\DashboardModule::class,
 *
 * Route naming mirrors NodeAdmin's convention:
 *   admin.v1.dashboard.index  →  GET /admin/v1/dashboard
 */
class DashboardModule
{
    public function __construct(
        private readonly AppConfig $config
    ) {
    }

    public function register(RouteCollector $r, RouteRegistry $registry): void
    {
        if ($this->config->isFullMode()) {
            $this->registerWebRoutes($r, $registry);
        }
    }

    // ─── Web routes ───────────────────────────────────────────────────────────

    private function registerWebRoutes(RouteCollector $r, RouteRegistry $registry): void
    {
        $r->addRoute('GET', '/admin/v1/dashboard', [DashboardController::class, 'index']);
        $registry->register('admin.v1.dashboard.index', 'GET', '/admin/v1/dashboard');
    }
}
