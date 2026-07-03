<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Media;

use FastRoute\RouteCollector;
use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\RouteRegistry;
use PHPAdmin\Modules\Media\Controllers\MediaController;

/**
 * MediaModule — registers JSON API routes for the editor media library.
 *
 * Activate in config/modules.php:
 *   PHPAdmin\Modules\Media\MediaModule::class,
 *
 * Routes registered:
 *   admin.v1.media.list    GET  /admin/v1/media/list
 *   admin.v1.media.upload  POST /admin/v1/media/upload
 *   admin.v1.media.delete  POST /admin/v1/media/delete
 *   admin.v1.media.file    GET  /admin/v1/media/file/{name}  (proxy → OSS presigned URL)
 */
class MediaModule
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

        // List media files
        $r->addRoute('GET', '/admin/v1/media/list', [MediaController::class, 'list']);
        $registry->register('admin.v1.media.list', 'GET', '/admin/v1/media/list');

        // Upload a new image
        $r->addRoute('POST', '/admin/v1/media/upload', [MediaController::class, 'upload']);
        $registry->register('admin.v1.media.upload', 'POST', '/admin/v1/media/upload');

        // Delete an image by key
        $r->addRoute('POST', '/admin/v1/media/delete', [MediaController::class, 'delete']);
        $registry->register('admin.v1.media.delete', 'POST', '/admin/v1/media/delete');

        // Proxy: redirect ke presigned OSS URL (bucket boleh private)
        $r->addRoute('GET', '/admin/v1/media/file/{name}', [MediaController::class, 'file']);
        $registry->register('admin.v1.media.file', 'GET', '/admin/v1/media/file/{name}');
    }
}
