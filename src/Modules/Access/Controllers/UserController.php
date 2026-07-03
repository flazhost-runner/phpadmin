<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Controllers;

use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\Middleware\AuthMiddleware;
use PHPAdmin\Core\Middleware\AuthorizeMiddleware;
use PHPAdmin\Core\SettingCache;
use PHPAdmin\Core\Themes;
use PHPAdmin\Modules\Access\Contracts\IUserService;
use PHPAdmin\Modules\Access\Contracts\IRoleService;
use PHPAdmin\Modules\Access\Models\User;

/**
 * UserController — thin controller for user CRUD (web + API).
 *
 * Each public method signature: ($routeVars, $flash, $errors, $oldInput).
 */
class UserController
{
    public function __construct(
        private readonly IUserService $userService,
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
        $user = $this->requireAuth();

        $filter = [
            'q_page_size' => (string)($_GET['q_page_size'] ?? '10'),
            'q_code'      => (string)($_GET['q_code']      ?? ''),
            'q_name'      => (string)($_GET['q_name']      ?? ''),
            'q_phone'     => (string)($_GET['q_phone']     ?? ''),
            'q_email'     => (string)($_GET['q_email']     ?? ''),
            'q_status'    => (string)($_GET['q_status']    ?? ''),
            'q_role'      => (string)($_GET['q_role']      ?? ''),
            'q_page'      => (string)($_GET['q_page']      ?? '1'),
        ];

        $perPage = max(1, (int)$filter['q_page_size']);
        $page    = max(1, (int)$filter['q_page']);

        $paginate = $this->userService->index($filter, $perPage, $page);
        $roles    = $this->roleService->index([], 1000, 1)['datas'];

        $this->renderAdmin('users/index.php', [
            'flash'    => $flash,
            'filter'   => $filter,
            'paginate' => $paginate,
            'roles'    => $roles,
            'authUser' => $user,
        ], 'User Management');
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function create(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $user  = $this->requireAuth();
        $roles = $this->roleService->index([], 1000, 1)['datas'];

        $this->renderAdmin('users/create.php', [
            'flash'     => $flash,
            'errors'    => $errors,
            'oldInput'  => $oldInput,
            'roles'     => $roles,
            'timezones' => \DateTimeZone::listIdentifiers(),
            'authUser'  => $user,
        ], 'Create User');
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
            'code'           => trim((string)($_POST['code']           ?? '')),
            'name'           => trim((string)($_POST['name']           ?? '')),
            'phone'          => trim((string)($_POST['phone']          ?? '')),
            'email'          => trim((string)($_POST['email']          ?? '')),
            'password'       => (string)($_POST['password']            ?? ''),
            'timezone'       => (string)($_POST['timezone']            ?? 'UTC'),
            'status'         => (string)($_POST['status']              ?? 'Active'),
            'blocked'        => isset($_POST['blocked']) && $_POST['blocked'] === '1',
            'blocked_reason' => trim((string)($_POST['blocked_reason'] ?? '')),
            'roles'          => (array)($_POST['roles']                ?? []),
            'created_by'     => (string)($_SESSION['user_id']          ?? ''),
            'updated_by'     => (string)($_SESSION['user_id']          ?? ''),
        ];

        // Handle picture upload
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            $data['picture'] = $this->savePicture($_FILES['picture']);
        }

        try {
            $this->userService->create($data);
            flash_success('Create User Success.');
            redirect(route('admin.v1.access.user.index'));
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            $_SESSION['_old_input'] = array_diff_key($data, ['password' => '', 'picture' => '']);
            redirect(route('admin.v1.access.user.create'));
        }
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
        $data  = $this->userService->findById($routeVars['id'] ?? '');
        $roles = $this->roleService->index([], 1000, 1)['datas'];

        $this->renderAdmin('users/edit.php', [
            'flash'     => $flash,
            'errors'    => $errors,
            'oldInput'  => $oldInput,
            'data'      => $data,
            'roles'     => $roles,
            'timezones' => \DateTimeZone::listIdentifiers(),
        ], 'Edit User');
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
        $id = $routeVars['id'] ?? '';

        $data = [
            'code'           => trim((string)($_POST['code']           ?? '')),
            'name'           => trim((string)($_POST['name']           ?? '')),
            'phone'          => trim((string)($_POST['phone']          ?? '')),
            'email'          => trim((string)($_POST['email']          ?? '')),
            'password'       => (string)($_POST['password']            ?? ''),
            'timezone'       => (string)($_POST['timezone']            ?? 'UTC'),
            'status'         => (string)($_POST['status']              ?? 'Active'),
            'blocked'        => isset($_POST['blocked']) && $_POST['blocked'] === '1',
            'blocked_reason' => trim((string)($_POST['blocked_reason'] ?? '')),
            'roles'          => (array)($_POST['roles']                ?? []),
            'updated_by'     => (string)($_SESSION['user_id']          ?? ''),
        ];

        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            $data['picture'] = $this->savePicture($_FILES['picture']);
        }

        try {
            $this->userService->update($id, $data);
            flash_success('Update User Success.');
            redirect(route('admin.v1.access.user.index'));
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            redirect(route('admin.v1.access.user.edit', ['id' => $id]));
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
            $this->userService->delete($routeVars['id'] ?? '');
            flash_success('Delete User Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.user.index'));
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
            $this->userService->deleteSelected(array_map('strval', $ids));
            flash_success('Selected users deleted.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        redirect(route('admin.v1.access.user.index'));
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
        $result  = $this->userService->index($filter, $perPage, $page);
        json_response(['status' => true, 'message' => 'Success', 'data' => $result]);
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
            $user = $this->userService->create($body);
            json_response(['status' => true, 'message' => 'Success', 'data' => $user->toArray()], 201);
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
            $user = $this->userService->findById($routeVars['id'] ?? '');
            json_response(['status' => true, 'message' => 'Success', 'data' => $user->toArray()]);
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
            $user = $this->userService->update($routeVars['id'] ?? '', $body);
            json_response(['status' => true, 'message' => 'Success', 'data' => $user->toArray()]);
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
            $this->userService->delete($routeVars['id'] ?? '');
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
        $ids  = (array)($body['ids'] ?? []);
        $this->userService->deleteSelected(array_map('strval', $ids));
        json_response(['status' => true, 'message' => 'Deleted.', 'data' => null]);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Enforce authentication + authorization; return the current user model.
     */
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
     * Buffer an access view and render it inside the admin_main layout.
     *
     * @param array<string, mixed> $viewData
     */
    private function renderAdmin(string $viewFile, array $viewData = [], string $title = 'PHPAdmin'): void
    {
        $setting   = SettingCache::get() ?? [];
        $themeName = (string)($setting['theme'] ?? 'Blue');

        try {
            $theme = Themes::get($themeName);
        } catch (\InvalidArgumentException) {
            $theme     = Themes::get('Blue');
            $themeName = 'Blue';
        }

        $currentUser = $viewData['authUser'] ?? null;

        $pageData = array_merge([
            'theme'       => $theme,
            'setting'     => $setting,
            'themeName'   => $themeName,
            'themes'      => Themes::all(),
            '_csrf'       => (string)($_SESSION['_csrf'] ?? ''),
            'currentUser' => $currentUser,
            'flash'       => [],
            'errors'      => [],
            'oldInput'    => [],
            'pageTitle'   => $title . ' — ' . $this->config->appName,
        ], $viewData);

        ob_start();
        extract($pageData, EXTR_SKIP);
        include $this->config->appRoot . '/src/views/access/' . $viewFile;
        $content = ob_get_clean();

        render(
            $this->config->appRoot . '/src/views/layouts/admin_main.php',
            array_merge($pageData, ['content' => $content])
        );
    }

    /**
     * Save an uploaded picture file; return the web-relative path.
     *
     * @param  array<string,mixed> $file  Entry from $_FILES
     */
    private function savePicture(array $file): string
    {
        $ext       = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $filename  = uuid() . ($ext !== '' ? '.' . $ext : '');
        $uploadDir = $this->config->appRoot . '/public/uploads/access/user/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        move_uploaded_file((string)($file['tmp_name'] ?? ''), $uploadDir . $filename);

        return 'uploads/access/user/' . $filename;
    }
}
