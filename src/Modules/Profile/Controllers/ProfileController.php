<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Profile\Controllers;

use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\Middleware\AuthMiddleware;
use PHPAdmin\Core\SettingCache;
use PHPAdmin\Core\Themes;
use PHPAdmin\Modules\Access\Models\User;
use PHPAdmin\Modules\Profile\Contracts\IProfileService;

/**
 * ProfileController — thin controller for the user self-service profile page.
 *
 * Routes:
 *   GET  /admin/v1/profile          → index()
 *   PUT  /admin/v1/profile/update   → update()
 */
class ProfileController
{
    public function __construct(
        private readonly IProfileService $profileService,
        private readonly AppConfig $config
    ) {
    }

    // ─── Web handlers ─────────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function index(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $user   = $this->requireAuth();
        $data   = $this->profileService->getProfile((string)$user->id);

        $this->renderProfile('profile/profile.php', [
            'flash'     => $flash,
            'errors'    => $errors,
            'oldInput'  => $oldInput,
            'data'      => $data,
            'timezones' => \DateTimeZone::listIdentifiers(),
            'authUser'  => $user,
        ], 'Profile');
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function update(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $user   = $this->requireAuth();
        $userId = (string)$user->id;

        $data = [
            'name'                  => trim((string)($_POST['name']                  ?? '')),
            'phone'                 => trim((string)($_POST['phone']                 ?? '')),
            'email'                 => trim((string)($_POST['email']                 ?? '')),
            'timezone'              => (string)($_POST['timezone']                   ?? 'UTC'),
            'password'              => (string)($_POST['password']                   ?? ''),
            'password_confirmation' => (string)($_POST['password_confirmation']      ?? ''),
            'status'                => (string)($_POST['status']                     ?? 'Active'),
        ];

        // Handle picture upload
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            $data['picture'] = $this->savePicture($_FILES['picture']);
        }

        try {
            $this->profileService->updateProfile($userId, $data);
            flash_success('Update Profile Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }

        redirect(route('admin.v1.profile.index'));
    }

    // ─── API handlers ────────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiIndex(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $payload = require_api_auth();
        $userId  = (string)($payload['sub'] ?? '');
        $data    = $this->profileService->getProfile($userId);
        $user    = $data['data'] ?? $data;
        json_response(['status' => true, 'message' => 'Success', 'data' => [
            'id'       => $user['id']       ?? '',
            'name'     => $user['name']     ?? '',
            'email'    => $user['email']    ?? '',
            'timezone' => $user['timezone'] ?? '',
            'picture'  => $user['picture']  ?? '',
            'status'   => $user['status']   ?? '',
        ]]);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Enforce session authentication; return the current User model.
     */
    private function requireAuth(): User
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        AuthMiddleware::run($uri);

        $userId = (string)($_SESSION['user_id'] ?? '');
        /** @var User|null $user */
        $user = User::with('roles')->find($userId);

        if ($user === null) {
            redirect(route('web.auth.login'));
        }

        return $user;
    }

    /**
     * Render a view inside the admin_main layout.
     *
     * @param array<string, mixed> $data
     */
    private function renderProfile(string $viewFile, array $data = [], string $title = 'PHPAdmin'): void
    {
        $setting   = SettingCache::get() ?? [];
        $themeName = (string)($setting['theme'] ?? 'Blue');

        try {
            $theme = Themes::get($themeName);
        } catch (\InvalidArgumentException) {
            $theme     = Themes::get('Blue');
            $themeName = 'Blue';
        }

        $currentUser = $data['authUser'] ?? null;

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
        ], $data);

        ob_start();
        extract($pageData, EXTR_SKIP);
        include $this->config->appRoot . '/src/views/' . $viewFile;
        $content = ob_get_clean();

        render(
            $this->config->appRoot . '/src/views/layouts/admin_main.php',
            array_merge($pageData, ['content' => $content])
        );
    }

    /**
     * Save an uploaded picture file; return the web-relative path.
     *
     * @param  array<string, mixed> $file  Entry from $_FILES.
     */
    private function savePicture(array $file): string
    {
        $ext       = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $filename  = uuid() . ($ext !== '' ? '.' . $ext : '');
        $uploadDir = $this->config->appRoot . '/public/uploads/profile/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        move_uploaded_file((string)($file['tmp_name'] ?? ''), $uploadDir . $filename);

        return 'uploads/profile/' . $filename;
    }
}
