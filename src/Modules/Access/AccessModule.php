<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access;

use FastRoute\RouteCollector;
use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\RouteRegistry;
use PHPAdmin\Modules\Access\Controllers\PermissionController;
use PHPAdmin\Modules\Access\Controllers\RoleController;
use PHPAdmin\Modules\Access\Controllers\UserController;

/**
 * AccessModule — registers all RBAC routes (web + API).
 *
 * Activate in config/modules.php:
 *   PHPAdmin\Modules\Access\AccessModule::class,
 *
 * Route naming mirrors NodeAdmin's convention exactly:
 *   admin.v1.access.{user|role|permission}.{action}
 *   api.v1.access.{user|role|permission}.{action}
 */
class AccessModule
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
        // ── User ──────────────────────────────────────────────────────────────
        $r->addRoute(
            'GET',
            '/admin/v1/access/user',
            [UserController::class, 'index']
        );
        $registry->register('admin.v1.access.user.index', 'GET', '/admin/v1/access/user');

        $r->addRoute(
            'GET',
            '/admin/v1/access/user/create',
            [UserController::class, 'create']
        );
        $registry->register('admin.v1.access.user.create', 'GET', '/admin/v1/access/user/create');

        $r->addRoute(
            'POST',
            '/admin/v1/access/user/store',
            [UserController::class, 'store']
        );
        $registry->register('admin.v1.access.user.store', 'POST', '/admin/v1/access/user/store');

        $r->addRoute(
            'GET',
            '/admin/v1/access/user/{id}/edit',
            [UserController::class, 'edit']
        );
        $registry->register('admin.v1.access.user.edit', 'GET', '/admin/v1/access/user/{id}/edit');

        $r->addRoute(
            'PUT',
            '/admin/v1/access/user/{id}/update',
            [UserController::class, 'update']
        );
        $registry->register('admin.v1.access.user.update', 'PUT', '/admin/v1/access/user/{id}/update');

        $r->addRoute(
            'DELETE',
            '/admin/v1/access/user/{id}/delete',
            [UserController::class, 'delete']
        );
        $registry->register('admin.v1.access.user.delete', 'DELETE', '/admin/v1/access/user/{id}/delete');

        $r->addRoute(
            'POST',
            '/admin/v1/access/user/delete_selected',
            [UserController::class, 'deleteSelected']
        );
        $registry->register('admin.v1.access.user.delete_selected', 'POST', '/admin/v1/access/user/delete_selected');

        // ── Role ──────────────────────────────────────────────────────────────
        $r->addRoute(
            'GET',
            '/admin/v1/access/role',
            [RoleController::class, 'index']
        );
        $registry->register('admin.v1.access.role.index', 'GET', '/admin/v1/access/role');

        $r->addRoute(
            'GET',
            '/admin/v1/access/role/create',
            [RoleController::class, 'create']
        );
        $registry->register('admin.v1.access.role.create', 'GET', '/admin/v1/access/role/create');

        $r->addRoute(
            'POST',
            '/admin/v1/access/role/store',
            [RoleController::class, 'store']
        );
        $registry->register('admin.v1.access.role.store', 'POST', '/admin/v1/access/role/store');

        $r->addRoute(
            'GET',
            '/admin/v1/access/role/{id}/edit',
            [RoleController::class, 'edit']
        );
        $registry->register('admin.v1.access.role.edit', 'GET', '/admin/v1/access/role/{id}/edit');

        $r->addRoute(
            'PUT',
            '/admin/v1/access/role/{id}/update',
            [RoleController::class, 'update']
        );
        $registry->register('admin.v1.access.role.update', 'PUT', '/admin/v1/access/role/{id}/update');

        $r->addRoute(
            'DELETE',
            '/admin/v1/access/role/{id}/delete',
            [RoleController::class, 'delete']
        );
        $registry->register('admin.v1.access.role.delete', 'DELETE', '/admin/v1/access/role/{id}/delete');

        $r->addRoute(
            'POST',
            '/admin/v1/access/role/delete_selected',
            [RoleController::class, 'deleteSelected']
        );
        $registry->register('admin.v1.access.role.delete_selected', 'POST', '/admin/v1/access/role/delete_selected');

        // Role permission management
        $r->addRoute(
            'GET',
            '/admin/v1/access/role/{id}/permission',
            [RoleController::class, 'showPermissions']
        );
        $registry->register('admin.v1.access.role.permission', 'GET', '/admin/v1/access/role/{id}/permission');

        $r->addRoute(
            'GET',
            '/admin/v1/access/role/{id}/permission/{permission_id}/assign',
            [RoleController::class, 'assignPermission']
        );
        $registry->register(
            'admin.v1.access.role.permission.assign',
            'GET',
            '/admin/v1/access/role/{id}/permission/{permission_id}/assign'
        );

        $r->addRoute(
            'POST',
            '/admin/v1/access/role/{id}/permission/assign_selected',
            [RoleController::class, 'assignSelected']
        );
        $registry->register(
            'admin.v1.access.role.permission.assign_selected',
            'POST',
            '/admin/v1/access/role/{id}/permission/assign_selected'
        );

        $r->addRoute(
            'GET',
            '/admin/v1/access/role/{id}/permission/{permission_id}/unassign',
            [RoleController::class, 'unassignPermission']
        );
        $registry->register(
            'admin.v1.access.role.permission.unassign',
            'GET',
            '/admin/v1/access/role/{id}/permission/{permission_id}/unassign'
        );

        $r->addRoute(
            'POST',
            '/admin/v1/access/role/{id}/permission/unassign_selected',
            [RoleController::class, 'unassignSelected']
        );
        $registry->register(
            'admin.v1.access.role.permission.unassign_selected',
            'POST',
            '/admin/v1/access/role/{id}/permission/unassign_selected'
        );

        // ── Permission ────────────────────────────────────────────────────────
        $r->addRoute(
            'GET',
            '/admin/v1/access/permission',
            [PermissionController::class, 'index']
        );
        $registry->register('admin.v1.access.permission.index', 'GET', '/admin/v1/access/permission');

        $r->addRoute(
            'GET',
            '/admin/v1/access/permission/create',
            [PermissionController::class, 'create']
        );
        $registry->register('admin.v1.access.permission.create', 'GET', '/admin/v1/access/permission/create');

        $r->addRoute(
            'POST',
            '/admin/v1/access/permission/store',
            [PermissionController::class, 'store']
        );
        $registry->register('admin.v1.access.permission.store', 'POST', '/admin/v1/access/permission/store');

        $r->addRoute(
            'GET',
            '/admin/v1/access/permission/{id}/edit',
            [PermissionController::class, 'edit']
        );
        $registry->register('admin.v1.access.permission.edit', 'GET', '/admin/v1/access/permission/{id}/edit');

        $r->addRoute(
            'PUT',
            '/admin/v1/access/permission/{id}/update',
            [PermissionController::class, 'update']
        );
        $registry->register('admin.v1.access.permission.update', 'PUT', '/admin/v1/access/permission/{id}/update');

        $r->addRoute(
            'DELETE',
            '/admin/v1/access/permission/{id}/delete',
            [PermissionController::class, 'delete']
        );
        $registry->register('admin.v1.access.permission.delete', 'DELETE', '/admin/v1/access/permission/{id}/delete');

        $r->addRoute(
            'POST',
            '/admin/v1/access/permission/delete_selected',
            [PermissionController::class, 'deleteSelected']
        );
        $registry->register(
            'admin.v1.access.permission.delete_selected',
            'POST',
            '/admin/v1/access/permission/delete_selected'
        );

        $r->addRoute(
            'GET',
            '/admin/v1/access/permission/sync',
            [PermissionController::class, 'sync']
        );
        $registry->register('admin.v1.access.permission.sync', 'GET', '/admin/v1/access/permission/sync');
    }

    // ─── API routes ───────────────────────────────────────────────────────────

    private function registerApiRoutes(RouteCollector $r, RouteRegistry $registry): void
    {
        // ── User ──────────────────────────────────────────────────────────────
        $r->addRoute(
            'GET',
            '/api/v1/access/user',
            [UserController::class, 'apiIndex']
        );
        $registry->register('api.v1.access.user.index', 'GET', '/api/v1/access/user');

        $r->addRoute(
            'POST',
            '/api/v1/access/user/store',
            [UserController::class, 'apiStore']
        );
        $registry->register('api.v1.access.user.store', 'POST', '/api/v1/access/user/store');

        $r->addRoute(
            'GET',
            '/api/v1/access/user/{id}/edit',
            [UserController::class, 'apiEdit']
        );
        $registry->register('api.v1.access.user.edit', 'GET', '/api/v1/access/user/{id}/edit');

        $r->addRoute(
            'PUT',
            '/api/v1/access/user/{id}/update',
            [UserController::class, 'apiUpdate']
        );
        $registry->register('api.v1.access.user.update', 'PUT', '/api/v1/access/user/{id}/update');

        $r->addRoute(
            'DELETE',
            '/api/v1/access/user/{id}/delete',
            [UserController::class, 'apiDelete']
        );
        $registry->register('api.v1.access.user.delete', 'DELETE', '/api/v1/access/user/{id}/delete');

        $r->addRoute(
            'POST',
            '/api/v1/access/user/delete_selected',
            [UserController::class, 'apiDeleteSelected']
        );
        $registry->register('api.v1.access.user.delete_selected', 'POST', '/api/v1/access/user/delete_selected');

        // ── Role ──────────────────────────────────────────────────────────────
        $r->addRoute(
            'GET',
            '/api/v1/access/role',
            [RoleController::class, 'apiIndex']
        );
        $registry->register('api.v1.access.role.index', 'GET', '/api/v1/access/role');

        $r->addRoute(
            'POST',
            '/api/v1/access/role/store',
            [RoleController::class, 'apiStore']
        );
        $registry->register('api.v1.access.role.store', 'POST', '/api/v1/access/role/store');

        $r->addRoute(
            'GET',
            '/api/v1/access/role/{id}/edit',
            [RoleController::class, 'apiEdit']
        );
        $registry->register('api.v1.access.role.edit', 'GET', '/api/v1/access/role/{id}/edit');

        $r->addRoute(
            'PUT',
            '/api/v1/access/role/{id}/update',
            [RoleController::class, 'apiUpdate']
        );
        $registry->register('api.v1.access.role.update', 'PUT', '/api/v1/access/role/{id}/update');

        $r->addRoute(
            'DELETE',
            '/api/v1/access/role/{id}/delete',
            [RoleController::class, 'apiDelete']
        );
        $registry->register('api.v1.access.role.delete', 'DELETE', '/api/v1/access/role/{id}/delete');

        $r->addRoute(
            'POST',
            '/api/v1/access/role/delete_selected',
            [RoleController::class, 'apiDeleteSelected']
        );
        $registry->register('api.v1.access.role.delete_selected', 'POST', '/api/v1/access/role/delete_selected');

        // Role permission API
        $r->addRoute(
            'GET',
            '/api/v1/access/role/{id}/permission',
            [RoleController::class, 'apiGetPermissions']
        );
        $registry->register('api.v1.access.role.permission.index', 'GET', '/api/v1/access/role/{id}/permission');

        $r->addRoute(
            'GET',
            '/api/v1/access/role/{id}/permission/{permission_id}/assign',
            [RoleController::class, 'apiAssignPermission']
        );
        $registry->register(
            'api.v1.access.role.permission.assign',
            'GET',
            '/api/v1/access/role/{id}/permission/{permission_id}/assign'
        );

        $r->addRoute(
            'POST',
            '/api/v1/access/role/{id}/permission/assign_selected',
            [RoleController::class, 'apiAssignSelected']
        );
        $registry->register(
            'api.v1.access.role.permission.assign_selected',
            'POST',
            '/api/v1/access/role/{id}/permission/assign_selected'
        );

        $r->addRoute(
            'GET',
            '/api/v1/access/role/{id}/permission/{permission_id}/unassign',
            [RoleController::class, 'apiUnassignPermission']
        );
        $registry->register(
            'api.v1.access.role.permission.unassign',
            'GET',
            '/api/v1/access/role/{id}/permission/{permission_id}/unassign'
        );

        $r->addRoute(
            'POST',
            '/api/v1/access/role/{id}/permission/unassign_selected',
            [RoleController::class, 'apiUnassignSelected']
        );
        $registry->register(
            'api.v1.access.role.permission.unassign_selected',
            'POST',
            '/api/v1/access/role/{id}/permission/unassign_selected'
        );

        // ── Permission ────────────────────────────────────────────────────────
        $r->addRoute(
            'GET',
            '/api/v1/access/permission',
            [PermissionController::class, 'apiIndex']
        );
        $registry->register('api.v1.access.permission.index', 'GET', '/api/v1/access/permission');

        $r->addRoute(
            'POST',
            '/api/v1/access/permission/store',
            [PermissionController::class, 'apiStore']
        );
        $registry->register('api.v1.access.permission.store', 'POST', '/api/v1/access/permission/store');

        $r->addRoute(
            'GET',
            '/api/v1/access/permission/{id}/edit',
            [PermissionController::class, 'apiEdit']
        );
        $registry->register('api.v1.access.permission.edit', 'GET', '/api/v1/access/permission/{id}/edit');

        $r->addRoute(
            'PUT',
            '/api/v1/access/permission/{id}/update',
            [PermissionController::class, 'apiUpdate']
        );
        $registry->register('api.v1.access.permission.update', 'PUT', '/api/v1/access/permission/{id}/update');

        $r->addRoute(
            'DELETE',
            '/api/v1/access/permission/{id}/delete',
            [PermissionController::class, 'apiDelete']
        );
        $registry->register('api.v1.access.permission.delete', 'DELETE', '/api/v1/access/permission/{id}/delete');

        $r->addRoute(
            'POST',
            '/api/v1/access/permission/delete_selected',
            [PermissionController::class, 'apiDeleteSelected']
        );
        $registry->register(
            'api.v1.access.permission.delete_selected',
            'POST',
            '/api/v1/access/permission/delete_selected'
        );

        $r->addRoute(
            'POST',
            '/api/v1/access/permission/sync',
            [PermissionController::class, 'apiSync']
        );
        $registry->register('api.v1.access.permission.sync', 'POST', '/api/v1/access/permission/sync');
    }
}
