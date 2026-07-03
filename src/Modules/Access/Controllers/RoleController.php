<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Controllers;

use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\Middleware\AuthMiddleware;
use PHPAdmin\Core\Middleware\AuthorizeMiddleware;
use PHPAdmin\Core\SettingCache;
use PHPAdmin\Core\Themes;
use PHPAdmin\Modules\Access\Contracts\IRoleService;
use PHPAdmin\Modules\Access\Models\User;

/**
 * RoleController — thin controller for role CRUD + permission assignment (web + API).
 */
class RoleController
{
    public function __construct(
        private readonly IRoleService $roleService,
        private readonly AppConfig $config
    ) {
    }

    // ─── Web: GET ────────────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function index(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();

        $filter = [
            'q_page_size' => (string)($_GET['q_page_size'] ?? '10'),
            'q_name'      => (string)($_GET['q_name']      ?? ''),
            'q_status'    => (string)($_GET['q_status']    ?? ''),
            'q_desc'      => (string)($_GET['q_desc']      ?? ''),
            'q_page'      => (string)($_GET['q_page']      ?? '1'),
        ];

        $perPage  = max(1, (int)$filter['q_page_size']);
        $page     = max(1, (int)$filter['q_page']);
        $paginate = $this->roleService->index($filter, $perPage, $page);

        $this->renderAdmin('roles/index.php', [
            'flash'    => $flash,
            'filter'   => $filter,
            'paginate' => $paginate,
        ], 'Role Management');
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function create(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $this->renderAdmin('roles/create.php', [
            'flash'    => $flash,
            'errors'   => $errors,
            'oldInput' => $oldInput,
        ], 'Create Role');
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function edit(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $data = $this->roleService->findById($routeVars['id'] ?? '');
        $this->renderAdmin('roles/edit.php', [
            'flash'    => $flash,
            'errors'   => $errors,
            'oldInput' => $oldInput,
            'data'     => $data,
        ], 'Edit Role');
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function showPermissions(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();

        $roleId = $routeVars['id'] ?? '';
        $filter = [
            'q_page_size' => (string)($_GET['q_page_size'] ?? '10'),
            'q_name'      => (string)($_GET['q_name']      ?? ''),
            'q_status'    => (string)($_GET['q_status']    ?? ''),
            'q_desc'      => (string)($_GET['q_desc']      ?? ''),
            'q_page'      => (string)($_GET['q_page']      ?? '1'),
        ];

        $perPage  = max(1, (int)$filter['q_page_size']);
        $page     = max(1, (int)$filter['q_page']);
        $result   = $this->roleService->getPermissions($roleId, $filter, $perPage, $page);

        $this->renderAdmin('roles/permission.php', [
            'flash'    => $flash,
            'filter'   => $filter,
            'paginate' => $result,
            'role'     => $result['role'],
        ], 'Role Permissions');
    }

    // ─── Web: POST / PUT / DELETE ─────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function store(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $data = [
            'name'       => trim((string)($_POST['name']   ?? '')),
            'status'     => (string)($_POST['status']      ?? 'Active'),
            'desc'       => trim((string)($_POST['desc']   ?? '')),
            'created_by' => (string)($_SESSION['user_id']  ?? ''),
            'updated_by' => (string)($_SESSION['user_id']  ?? ''),
        ];
        try {
            $this->roleService->create($data);
            flash_success('Create Role Success.');
            redirect(route('admin.v1.access.role.index'));
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            $_SESSION['_old_input'] = $data;
            redirect(route('admin.v1.access.role.create'));
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function update(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $id   = $routeVars['id'] ?? '';
        $data = [
            'name'       => trim((string)($_POST['name']   ?? '')),
            'status'     => (string)($_POST['status']      ?? 'Active'),
            'desc'       => trim((string)($_POST['desc']   ?? '')),
            'updated_by' => (string)($_SESSION['user_id']  ?? ''),
        ];
        try {
            $this->roleService->update($id, $data);
            flash_success('Update Role Success.');
            redirect(route('admin.v1.access.role.index'));
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            redirect(route('admin.v1.access.role.edit', ['id' => $id]));
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function delete(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        try {
            $this->roleService->delete($routeVars['id'] ?? '');
            flash_success('Delete Role Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.role.index'));
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function deleteSelected(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $ids = (array)($_POST['selected'] ?? []);
        try {
            $this->roleService->deleteSelected(array_map('strval', $ids));
            flash_success('Delete Role Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.role.index'));
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function assignPermission(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $roleId = $routeVars['id']            ?? '';
        $permId = $routeVars['permission_id'] ?? '';
        try {
            $this->roleService->assignPermission($roleId, $permId);
            flash_success('Assign Permission Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.role.permission', ['id' => $roleId]));
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function unassignPermission(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $roleId = $routeVars['id']            ?? '';
        $permId = $routeVars['permission_id'] ?? '';
        try {
            $this->roleService->unassignPermission($roleId, $permId);
            flash_success('Unassign Permission Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.role.permission', ['id' => $roleId]));
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function assignSelected(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $roleId  = $routeVars['id'] ?? '';
        $permIds = array_map('strval', (array)($_POST['selected'] ?? []));
        try {
            $this->roleService->assignSelected($roleId, $permIds);
            flash_success('Assign Permission Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.role.permission', ['id' => $roleId]));
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function unassignSelected(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $roleId  = $routeVars['id'] ?? '';
        $permIds = array_map('strval', (array)($_POST['selected'] ?? []));
        try {
            $this->roleService->unassignSelected($roleId, $permIds);
            flash_success('Unassign Permission Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.role.permission', ['id' => $roleId]));
    }

    // ─── API ─────────────────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiIndex(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        require_api_auth();
        $filter  = $_GET;
        $perPage = max(1, (int)($filter['q_page_size'] ?? 10));
        $page    = max(1, (int)($filter['q_page']      ?? 1));
        json_response([
            'status'  => true,
            'message' => 'Success',
            'data'    => $this->roleService->index($filter, $perPage, $page),
        ]);
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiStore(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        /** @var array<string,mixed> $body */
        $body = json_decode((string)file_get_contents('php://input'), true) ?? [];
        try {
            $role = $this->roleService->create($body);
            json_response(['status' => true, 'message' => 'Success', 'data' => $role->toArray()], 201);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiEdit(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        try {
            $role = $this->roleService->findById($routeVars['id'] ?? '');
            json_response(['status' => true, 'message' => 'Success', 'data' => $role->toArray()]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 404);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiUpdate(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        /** @var array<string,mixed> $body */
        $body = json_decode((string)file_get_contents('php://input'), true) ?? [];
        try {
            $role = $this->roleService->update($routeVars['id'] ?? '', $body);
            json_response(['status' => true, 'message' => 'Success', 'data' => $role->toArray()]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiDelete(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        try {
            $this->roleService->delete($routeVars['id'] ?? '');
            json_response(['status' => true, 'message' => 'Deleted.', 'data' => null]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 404);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiDeleteSelected(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        /** @var array<string,mixed> $body */
        $body = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $ids  = array_map('strval', (array)($body['ids'] ?? []));
        $this->roleService->deleteSelected($ids);
        json_response(['status' => true, 'message' => 'Deleted.', 'data' => null]);
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiGetPermissions(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $filter  = $_GET;
        $perPage = max(1, (int)($filter['q_page_size'] ?? 10));
        $page    = max(1, (int)($filter['q_page']      ?? 1));
        try {
            $result = $this->roleService->getPermissions($routeVars['id'] ?? '', $filter, $perPage, $page);
            unset($result['role']); // strip Eloquent model before JSON encode
            json_response(['status' => true, 'message' => 'Success', 'data' => $result]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 404);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiAssignPermission(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        try {
            $this->roleService->assignPermission($routeVars['id'] ?? '', $routeVars['permission_id'] ?? '');
            json_response(['status' => true, 'message' => 'Permission assigned.', 'data' => null]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiUnassignPermission(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        try {
            $this->roleService->unassignPermission($routeVars['id'] ?? '', $routeVars['permission_id'] ?? '');
            json_response(['status' => true, 'message' => 'Permission unassigned.', 'data' => null]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiAssignSelected(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        /** @var array<string,mixed> $body */
        $body    = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $permIds = array_map('strval', (array)($body['permission_ids'] ?? []));
        try {
            $this->roleService->assignSelected($routeVars['id'] ?? '', $permIds);
            json_response(['status' => true, 'message' => 'Permissions assigned.', 'data' => null]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiUnassignSelected(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        /** @var array<string,mixed> $body */
        $body    = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $permIds = array_map('strval', (array)($body['permission_ids'] ?? []));
        try {
            $this->roleService->unassignSelected($routeVars['id'] ?? '', $permIds);
            json_response(['status' => true, 'message' => 'Permissions unassigned.', 'data' => null]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

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
     * @param array<string, mixed> $data
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

        $pageData = array_merge([
            'theme'       => $theme,
            'setting'     => $setting,
            'themeName'   => $themeName,
            'themes'      => Themes::all(),
            '_csrf'       => (string)($_SESSION['_csrf'] ?? ''),
            'currentUser' => null,
            'flash'       => [],
            'errors'      => [],
            'oldInput'    => [],
            'pageTitle'   => $title . ' — ' . $this->config->appName,
        ], $data);

        ob_start();
        extract($pageData, EXTR_SKIP);
        include $this->config->appRoot . '/src/views/access/' . $viewFile;
        $content = ob_get_clean();

        render(
            $this->config->appRoot . '/src/views/layouts/admin_main.php',
            array_merge($pageData, ['content' => $content])
        );
    }
}
