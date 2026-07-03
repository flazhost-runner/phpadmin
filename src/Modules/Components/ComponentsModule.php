<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Components;

use FastRoute\RouteCollector;
use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\RouteRegistry;
use PHPAdmin\Modules\Components\Controllers\ComponentsController;

/**
 * ComponentsModule — registers the UI component showcase route.
 *
 * Activate in config/modules.php:
 *   PHPAdmin\Modules\Components\ComponentsModule::class,
 *
 * Route naming mirrors NodeAdmin's convention:
 *   admin.v1.components.index  →  GET /admin/v1/components
 */
class ComponentsModule
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
        $r->addRoute('GET', '/admin/v1/components', [ComponentsController::class, 'index']);
        $registry->register('admin.v1.components.index', 'GET', '/admin/v1/components');
    }
}
