<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Setting\Controllers;

use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\Middleware\AuthMiddleware;
use PHPAdmin\Core\Middleware\AuthorizeMiddleware;
use PHPAdmin\Core\SettingCache;
use PHPAdmin\Core\Themes;
use PHPAdmin\Modules\Access\Models\User;
use PHPAdmin\Modules\Home\Contracts\IFeTemplateService;
use PHPAdmin\Modules\Setting\Contracts\ISettingService;

/**
 * SettingController — thin controller for reading / updating global settings
 * and serving FE template previews (web + API).
 */
class SettingController
{
    public function __construct(
        private readonly ISettingService $settingService,
        private readonly IFeTemplateService $feTemplateService,
        private readonly AppConfig $config
    ) {
    }

    // ─── Web: GET ─────────────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function index(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();

        $data = $this->settingService->get();

        $feActive = (string)(($data['fe_template'] ?? '') ?: 'agency-consulting-002-creative-agency');

        $filter = [
            'q_name'      => (string)($_GET['q_name']      ?? ''),
            'q_category'  => (string)($_GET['q_category']  ?? ''),
            'q_page_size' => (string)($_GET['q_page_size'] ?? '12'),
            'q_page'      => (string)($_GET['q_page']      ?? '1'),
        ];

        $catalog      = $this->settingService->catalogPaginate($filter, $feActive);
        $feCategories = $this->settingService->catalogCategories();

        $this->renderAdmin('index.php', [
            'flash'         => $flash,
            'data'          => $data,
            'feActive'      => $feActive,
            'feTemplates'   => $catalog['datas'],
            'paginate_data' => $catalog['paginate_data'],
            'feCategories'  => $feCategories,
            'filter'        => $filter,
            'feCachedSlugs' => $this->feTemplateService->cachedSlugs(),
        ], 'Setting Management');
    }

    // ─── Web: PUT ─────────────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function update(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();

        $data = [
            'initial'     => trim((string)($_POST['initial']     ?? '')),
            'name'        => trim((string)($_POST['name']        ?? '')),
            'description' => (string)($_POST['description']      ?? ''),
            'phone'       => trim((string)($_POST['phone']       ?? '')),
            'address'     => trim((string)($_POST['address']     ?? '')),
            'email'       => trim((string)($_POST['email']       ?? '')),
            'copyright'   => trim((string)($_POST['copyright']   ?? '')),
            'theme'       => (string)($_POST['theme']            ?? ''),
            'fe_template' => (string)($_POST['fe_template']      ?? ''),
            'updated_by'  => (string)($_SESSION['user_id']       ?? ''),
        ];

        // Handle file uploads (icon, logo, favicon, login_image).
        foreach (['icon', 'logo', 'favicon', 'login_image'] as $field) {
            if (!empty($_FILES[$field]['tmp_name'])) {
                $path = $this->saveUpload($_FILES[$field], $field);
                if ($path !== null) {
                    $data[$field] = $path;
                }
            }
        }

        try {
            $this->settingService->update($data);

            // Download template HTML jika slug baru dipilih dan belum ter-cache lokal.
            $slug = $data['fe_template'];
            if ($slug !== '') {
                try {
                    $this->feTemplateService->ensure($slug);
                } catch (\Throwable) {
                    // Template caching is best-effort; setting was already saved successfully
                }
            }

            flash_success('Save Setting Success.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }

        redirect(route('admin.v1.setting.index'));
    }

    /**
     * Serve the raw HTML of one FE template for thumbnail / modal preview.
     * Validates slug, checks local cache, fetches upstream on miss.
     *
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function fePreview(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $slug = (string)($routeVars['slug'] ?? '');

        try {
            $html = $this->settingService->previewTemplate($slug);
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: public, max-age=3600');
            echo $html;
        } catch (\Throwable $e) {
            http_response_code($e->getCode() >= 400 ? $e->getCode() : 502);
            echo '<p style="font-family:sans-serif;padding:40px">Gagal memuat preview.</p>';
        }
        exit;
    }

    // ─── API ─────────────────────────────────────────────────────────────────

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
            $result = $this->settingService->update($body);
            json_response(['status' => true, 'message' => 'Success', 'data' => $result]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }
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
        require_api_auth();
        $data = $this->settingService->get();
        json_response(['status' => true, 'message' => 'Success', 'data' => [
            'id'          => $data['id']          ?? '',
            'name'        => $data['name']        ?? '',
            'theme'       => $data['theme']       ?? '',
            'fe_template' => $data['fe_template'] ?? '',
        ]]);
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
     * @param array<string,mixed> $data
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
        include $this->config->appRoot . '/src/views/setting/' . $viewFile;
        $content = ob_get_clean();

        render(
            $this->config->appRoot . '/src/views/layouts/admin_main.php',
            array_merge($pageData, ['content' => $content])
        );
    }

    /**
     * Persist an uploaded file to public/uploads/setting/ and return its
     * web-relative path (e.g. 'uploads/setting/xxx.jpg'), or null on failure.
     *
     * @param  array<string,mixed> $file  Entry from $_FILES
     */
    private function saveUpload(array $file, string $field): ?string
    {
        $tmpName = (string)($file['tmp_name'] ?? '');
        $origName = (string)($file['name']    ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return null;
        }

        $ext       = strtolower((string)pathinfo($origName, PATHINFO_EXTENSION));
        $filename  = $field . '_' . uuid() . ($ext !== '' ? '.' . $ext : '');
        $uploadDir = $this->config->appRoot . '/public/uploads/setting/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($tmpName, $uploadDir . $filename)) {
            return null;
        }

        return 'uploads/setting/' . $filename;
    }
}
