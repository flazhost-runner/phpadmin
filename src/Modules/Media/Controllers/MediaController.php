<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Media\Controllers;

use PHPAdmin\Core\Middleware\AuthMiddleware;
use PHPAdmin\Modules\Media\Contracts\IMediaService;

/**
 * MediaController — JSON API for the editor media library.
 *
 * All responses are JSON. CSRF is validated via the x-csrf-token header
 * (standard SPA/AJAX pattern) for mutating endpoints.
 *
 * Routes:
 *   GET  .../media/list    → list()
 *   POST .../media/upload  → upload()
 *   POST .../media/delete  → delete()
 */
class MediaController
{
    public function __construct(
        private readonly IMediaService $mediaService
    ) {
    }

    // ─── Handlers ─────────────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function list(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();

        $files = $this->mediaService->list();
        json_response(['status' => true, 'message' => 'Success', 'data' => $files]);
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function upload(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $this->validateCsrfHeader();

        if (!isset($_FILES['file'])) {
            json_response(['status' => false, 'message' => 'No file provided.', 'data' => null], 400);
        }

        try {
            $result = $this->mediaService->upload($_FILES['file']);
            json_response(['status' => true, 'message' => 'Success', 'data' => $result], 201);
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
    public function delete(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();
        $this->validateCsrfHeader();

        /** @var array<string,mixed> $body */
        $body = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $key  = trim((string)($body['key'] ?? ''));

        if ($key === '') {
            json_response(['status' => false, 'message' => 'Missing key.', 'data' => null], 400);
        }

        try {
            $this->mediaService->delete($key);
            json_response(['status' => true, 'message' => 'Deleted.', 'data' => null]);
        } catch (\Throwable $e) {
            $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 422;
            json_response(['status' => false, 'message' => $e->getMessage()], $code);
        }
    }

    /**
     * Proxy route — redirect 302 ke presigned OSS URL untuk satu file editor.
     *
     * URL yang disimpan di DB adalah /admin/v1/media/file/{name} (stabil, tidak
     * expired). Presigned URL di-generate fresh tiap akses ke route ini.
     * Bila OSS tidak dikonfigurasi, fallback mengembalikan 404.
     *
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function file(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->requireAuth();

        // Sanitasi: hanya karakter aman (UUID + ekstensi)
        $name = preg_replace('/[^A-Za-z0-9._-]/', '', (string)($routeVars['name'] ?? ''));
        if ($name === '') {
            http_response_code(400);
            echo 'Invalid file name.';
            exit;
        }

        $ossKey = 'media/editor/' . $name;
        try {
            $url = $this->mediaService->signedUrl($ossKey);
            header('Location: ' . $url, true, 302);
        } catch (\Throwable) {
            http_response_code(404);
            echo 'Not found.';
        }
        exit;
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Require an active session; redirect to login if not authenticated.
     */
    private function requireAuth(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        AuthMiddleware::run($uri);

        if (empty($_SESSION['user_id'])) {
            json_response(['status' => false, 'message' => 'Unauthenticated.', 'data' => null], 401);
        }
    }

    /**
     * Validate CSRF token from the x-csrf-token request header.
     */
    private function validateCsrfHeader(): void
    {
        $headerToken  = (string)(
            $_SERVER['HTTP_X_CSRF_TOKEN'] ??
            $_SERVER['HTTP_X-CSRF-TOKEN'] ?? ''
        );
        $sessionToken = (string)($_SESSION['_csrf'] ?? '');

        if ($sessionToken === '' || !hash_equals($sessionToken, $headerToken)) {
            json_response(['status' => false, 'message' => 'CSRF token mismatch.', 'data' => null], 419);
        }
    }
}
