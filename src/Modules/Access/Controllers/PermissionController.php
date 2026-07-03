<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Controllers;

use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\Middleware\AuthMiddleware;
use PHPAdmin\Core\Middleware\AuthorizeMiddleware;
use PHPAdmin\Core\SettingCache;
use PHPAdmin\Core\Themes;
use PHPAdmin\Modules\Access\Contracts\IPermissionService;
use PHPAdmin\Modules\Access\Models\User;

/**
 * PermissionController — thin controller for permission CRUD + sync (web + API).
 */
class PermissionController
{
    public function __construct(
        private readonly IPermissionService $permissionService,
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
            'q_guard'     => (string)($_GET['q_guard']     ?? ''),
            'q_method'    => (string)($_GET['q_method']    ?? ''),
            'q_status'    => (string)($_GET['q_status']    ?? ''),
            'q_desc'      => (string)($_GET['q_desc']      ?? ''),
            'q_page'      => (string)($_GET['q_page']      ?? '1'),
        ];

        // Lazy auto-sync: upsert routes from RouteRegistry into permissions table on every page open
        $this->permissionService->syncFromRoutes();

        $perPage  = max(1, (int)$filter['q_page_size']);
        $page     = max(1, (int)$filter['q_page']);
        $paginate = $this->permissionService->index($filter, $perPage, $page);

        $this->renderAdmin('permission/index.php', [
            'flash'    => $flash,
            'filter'   => $filter,
            'paginate' => $paginate,
        ], 'Permission Management');
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
        $this->renderAdmin('permission/create.php', [
            'flash'    => $flash,
            'errors'   => $errors,
            'oldInput' => $oldInput,
        ], 'Create Permission');
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
        $data = $this->permissionService->findById($routeVars['id'] ?? '');
        $this->renderAdmin('permission/edit.php', [
            'flash'    => $flash,
            'errors'   => $errors,
            'oldInput' => $oldInput,
            'data'     => $data,
        ], 'Edit Permission');
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
            'name'       => trim((string)($_POST['name']       ?? '')),
            'guard_name' => (string)($_POST['guard_name']      ?? 'web'),
            'method'     => trim((string)($_POST['method']     ?? '')),
            'status'     => (string)($_POST['status']          ?? 'Active'),
            'desc'       => trim((string)($_POST['desc']       ?? '')),
            'created_by' => (string)($_SESSION['user_id']      ?? ''),
            'updated_by' => (string)($_SESSION['user_id']      ?? ''),
        ];
        try {
            $this->permissionService->create($data);
            flash_success('Create Permission Success.');
            redirect(route('admin.v1.access.permission.index'));
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            $_SESSION['_old_input'] = $data;
            redirect(route('admin.v1.access.permission.create'));
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
            'name'       => trim((string)($_POST['name']       ?? '')),
            'guard_name' => (string)($_POST['guard_name']      ?? 'web'),
            'method'     => trim((string)($_POST['method']     ?? '')),
            'status'     => (string)($_POST['status']          ?? 'Active'),
            'desc'       => trim((string)($_POST['desc']       ?? '')),
            'updated_by' => (string)($_SESSION['user_id']      ?? ''),
        ];
        try {
            $this->permissionService->update($id, $data);
            flash_success('Update Permission Success.');
            redirect(route('admin.v1.access.permission.index'));
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            redirect(route('admin.v1.access.permission.edit', ['id' => $id]));
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
            $this->permissionService->delete($routeVars['id'] ?? '');
            flash_success('Delete Permission Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.permission.index'));
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
            $this->permissionService->deleteSelected(array_map('strval', $ids));
            flash_success('Delete Permission Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.permission.index'));
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function sync(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        try {
            $this->permissionService->syncFromRoutes();
            flash_success('Sync Permission Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.permission.index'));
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
            'data'    => $this->permissionService->index($filter, $perPage, $page),
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
            $perm = $this->permissionService->create($body);
            json_response(['status' => true, 'message' => 'Success', 'data' => $perm->toArray()], 201);
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
            $perm = $this->permissionService->findById($routeVars['id'] ?? '');
            json_response(['status' => true, 'message' => 'Success', 'data' => $perm->toArray()]);
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
            $perm = $this->permissionService->update($routeVars['id'] ?? '', $body);
            json_response(['status' => true, 'message' => 'Success', 'data' => $perm->toArray()]);
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
            $this->permissionService->delete($routeVars['id'] ?? '');
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
        $this->permissionService->deleteSelected($ids);
        json_response(['status' => true, 'message' => 'Deleted.', 'data' => null]);
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiSync(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        try {
            $this->permissionService->syncFromRoutes();
            json_response(['status' => true, 'message' => 'Permissions synced.', 'data' => null]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 500);
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
