<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Setting;

use FastRoute\RouteCollector;
use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\RouteRegistry;
use PHPAdmin\Modules\Setting\Controllers\SettingController;

/**
 * SettingModule — registers all Setting routes (web + API).
 *
 * Activate in config/modules.php:
 *   PHPAdmin\Modules\Setting\SettingModule::class,
 *
 * Route naming mirrors NodeAdmin's convention exactly:
 *   admin.v1.setting.{action}
 *   api.v1.setting.{action}
 */
class SettingModule
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

        $this->registerApiRoutes($r, $registry);
    }

    // ─── Web routes ───────────────────────────────────────────────────────────

    private function registerWebRoutes(RouteCollector $r, RouteRegistry $registry): void
    {
        $r->addRoute(
            'GET',
            '/admin/v1/setting',
            [SettingController::class, 'index']
        );
        $registry->register('admin.v1.setting.index', 'GET', '/admin/v1/setting');

        $r->addRoute(
            'PUT',
            '/admin/v1/setting/update',
            [SettingController::class, 'update']
        );
        $registry->register('admin.v1.setting.update', 'PUT', '/admin/v1/setting/update');

        $r->addRoute(
            'GET',
            '/admin/v1/setting/fe-preview/{slug}',
            [SettingController::class, 'fePreview']
        );
        $registry->register('admin.v1.setting.fe_preview', 'GET', '/admin/v1/setting/fe-preview/{slug}');
    }

    // ─── API routes ───────────────────────────────────────────────────────────

    private function registerApiRoutes(RouteCollector $r, RouteRegistry $registry): void
    {
        $r->addRoute(
            'GET',
            '/api/v1/setting',
            [SettingController::class, 'apiIndex']
        );
        $registry->register('api.v1.setting.index', 'GET', '/api/v1/setting');

        $r->addRoute(
            'PUT',
            '/api/v1/setting/update',
            [SettingController::class, 'apiUpdate']
        );
        $registry->register('api.v1.setting.update', 'PUT', '/api/v1/setting/update');
    }
}
