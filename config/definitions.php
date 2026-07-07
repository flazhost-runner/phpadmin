<?php

declare(strict_types=1);

use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\RouteRegistry;
use Predis\Client as PredisClient;
use function DI\factory;
use function DI\get;

return [

    // ─── Core ────────────────────────────────────────────────────────────────

    AppConfig::class => factory(function (): AppConfig {
        return new AppConfig();
    }),

    RouteRegistry::class => factory(function (): RouteRegistry {
        return RouteRegistry::getInstance();
    }),

    PredisClient::class => factory(function (AppConfig $config): PredisClient {
        // Params include TLS + SNI (peer_name) when REDIS_URL uses rediss://.
        return new PredisClient($config->redisParameters());
    }),

    // ─── Access module ───────────────────────────────────────────────────────
    PHPAdmin\Modules\Access\Contracts\IUserService::class =>
        get(PHPAdmin\Modules\Access\Services\UserService::class),

    PHPAdmin\Modules\Access\Contracts\IRoleService::class =>
        get(PHPAdmin\Modules\Access\Services\RoleService::class),

    PHPAdmin\Modules\Access\Contracts\IPermissionService::class =>
        get(PHPAdmin\Modules\Access\Services\PermissionService::class),

    // ─── Auth module ─────────────────────────────────────────────────────────
    PHPAdmin\Modules\Auth\Contracts\IAuthService::class =>
        get(PHPAdmin\Modules\Auth\Services\AuthService::class),

    // ─── Dashboard module ────────────────────────────────────────────────────
    PHPAdmin\Modules\Dashboard\Contracts\IDashboardService::class =>
        get(PHPAdmin\Modules\Dashboard\Services\DashboardService::class),

    // ─── Setting module ──────────────────────────────────────────────────────
    PHPAdmin\Modules\Setting\Contracts\ISettingService::class =>
        get(PHPAdmin\Modules\Setting\Services\SettingService::class),

    // ─── Profile module ──────────────────────────────────────────────────────
    PHPAdmin\Modules\Profile\Contracts\IProfileService::class =>
        get(PHPAdmin\Modules\Profile\Services\ProfileService::class),

    // ─── Media module ────────────────────────────────────────────────────────
    PHPAdmin\Core\OssService::class => factory(function (AppConfig $config): PHPAdmin\Core\OssService {
        return new PHPAdmin\Core\OssService($config);
    }),

    PHPAdmin\Modules\Media\Contracts\IMediaService::class => factory(
        function (PHPAdmin\Core\OssService $oss, AppConfig $config): PHPAdmin\Modules\Media\Services\MediaService {
            return new PHPAdmin\Modules\Media\Services\MediaService($oss, $config->appRoot);
        }
    ),

    // ─── Home module ─────────────────────────────────────────────────────────
    PHPAdmin\Modules\Home\Contracts\IFeCatalogService::class =>
        get(PHPAdmin\Modules\Home\Services\FeCatalogService::class),

    PHPAdmin\Modules\Home\Contracts\IFeTemplateService::class =>
        get(PHPAdmin\Modules\Home\Services\FeTemplateService::class),

];
