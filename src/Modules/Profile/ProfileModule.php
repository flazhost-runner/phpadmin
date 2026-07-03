<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Profile;

use FastRoute\RouteCollector;
use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\RouteRegistry;
use PHPAdmin\Modules\Profile\Controllers\ProfileController;

/**
 * ProfileModule — registers self-service profile routes (web only, full mode).
 *
 * Activate in config/modules.php:
 *   PHPAdmin\Modules\Profile\ProfileModule::class,
 *
 * Routes registered:
 *   admin.v1.profile.index   GET  /admin/v1/profile
 *   admin.v1.profile.update  PUT  /admin/v1/profile/update
 */
class ProfileModule
{
    public function __construct(
        private readonly AppConfig $config
    ) {
    }

    public function register(RouteCollector $r, RouteRegistry $registry): void
    {
        if (!$this->config->isFullMode()) {
            return;
        }

        // GET — show profile form
        $r->addRoute('GET', '/admin/v1/profile', [ProfileController::class, 'index']);
        $registry->register('admin.v1.profile.index', 'GET', '/admin/v1/profile');

        // PUT — update profile (submitted via POST + _method=PUT)
        $r->addRoute('PUT', '/admin/v1/profile/update', [ProfileController::class, 'update']);
        $registry->register('admin.v1.profile.update', 'PUT', '/admin/v1/profile/update');

        // API: GET — return profile as JSON (JWT auth)
        $r->addRoute('GET', '/api/v1/profile', [ProfileController::class, 'apiIndex']);
        $registry->register('api.v1.profile.index', 'GET', '/api/v1/profile');
    }
}
