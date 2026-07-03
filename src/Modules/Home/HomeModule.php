<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Home;

use FastRoute\RouteCollector;
use PHPAdmin\Core\RouteRegistry;
use PHPAdmin\Modules\Home\Controllers\HomeController;

/**
 * HomeModule — registers the public-facing landing page routes.
 *
 * Activate in config/modules.php:
 *   PHPAdmin\Modules\Home\HomeModule::class,
 *
 * Routes registered:
 *   web.home.root   GET  /
 *   web.home.index  GET  /home
 */
class HomeModule
{
    public function register(RouteCollector $r, RouteRegistry $registry): void
    {
        // Root path
        $r->addRoute('GET', '/', [HomeController::class, 'index']);
        $registry->register('web.home.root', 'GET', '/');

        // /home alias
        $r->addRoute('GET', '/home', [HomeController::class, 'index']);
        $registry->register('web.home.index', 'GET', '/home');
    }
}
