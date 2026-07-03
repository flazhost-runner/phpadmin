<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Media\Services;

use PHPAdmin\Core\Exceptions\AppException;
use PHPAdmin\Core\Exceptions\ValidationAppException;
use PHPAdmin\Core\OssService;
use PHPAdmin\Modules\Media\Contracts\IMediaService;

/**
 * MediaService — upload, list, delete gambar untuk editor media library.
 *
 * Storage mode (otomatis berdasarkan konfigurasi .env):
 *   OSS   → STORAGE_ACCESS_KEY_ID + STORAGE_BUCKET terisi: upload ke S3-compatible bucket,
 *            list dari bucket, akses via proxy /admin/v1/media/file/{name}
 *            yang redirect ke presigned URL (bucket boleh private).
 *   Local → fallback: simpan ke storage/editor/ (symlink public/storage/editor).
 *
 * Security (keduanya):
 *   - Magic-byte validation via finfo (bukan hanya MIME dari client).
 *   - GD re-encoding: file tidak pernah disimpan as-is; metadata & payload dibuang.
 *   - Delete: key divalidasi prefix + no ".." traversal.
 *
 * Port dari NodeAdmin MediaService.ts + fileService.ts (ali-oss → OssService S3).
 */
class MediaService implements IMediaService
{
    private const OSS_PREFIX  = 'media/editor/';
    private const LOCAL_DIR   = 'storage/editor';
    private const PUBLIC_PATH = '/storage/editor';

    /** Allowed MIME types → [GD loader fn, GD saver fn, extension] */
    private const ALLOWED = [
        'image/jpeg' => ['imagecreatefromjpeg', 'imagejpeg', 'jpg'],
        'image/png'  => ['imagecreatefrompng',  'imagepng',  'png'],
        'image/gif'  => ['imagecreatefromgif',  'imagegif',  'gif'],
        'image/webp' => ['imagecreatefromwebp', 'imagewebp', 'webp'],
    ];

    private string $appRoot;

    public function __construct(
        private readonly OssService $oss,
        string $appRoot = ''
    ) {
        $this->appRoot = $appRoot !== '' ? $appRoot : (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4));
    }

    // ─── IMediaService ────────────────────────────────────────────────────────

    public function isOss(): bool
    {
        return $this->oss->isConfigured();
    }

    public function signedUrl(string $ossKey): string
    {
        return $this->oss->signedUrl($ossKey);
    }

    public function list(): array
    {
        return $this->oss->isConfigured() ? $this->listFromOss() : $this->listLocal();
    }

    public function upload(array $file): array
    {
        // ── Validate file ─────────────────────────────────────────────────────
        if (!isset($file['tmp_name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ValidationAppException('No valid file uploaded.', ['file' => 'No valid file uploaded.']);
        }
        $tmpPath = (string)$file['tmp_name'];

        // Magic-byte detection (first 16 bytes via finfo)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new AppException('Cannot open finfo; server configuration issue.', 500);
        }
        $handle = @fopen($tmpPath, 'rb');
        if ($handle === false) {
            finfo_close($finfo);
            throw new AppException('Cannot read uploaded file.', 400);
        }
        $bytes = fread($handle, 16);
        fclose($handle);
        $mime     = finfo_buffer($finfo, $bytes !== false ? $bytes : '', FILEINFO_MIME_TYPE);
        @finfo_close($finfo); // deprecated no-op PHP 8.5+, suppress warning

        // Fallback: full-file MIME check for reliability
        $fullMime     = (string)@mime_content_type($tmpPath);
        $detectedMime = isset(self::ALLOWED[$mime]) ? $mime : $fullMime;

        if (!isset(self::ALLOWED[$detectedMime])) {
            throw new ValidationAppException(
                'Unsupported image type. Allowed: jpeg, png, gif, webp.',
                ['file' => 'Unsupported image type.']
            );
        }

        [$loader, $saver, $ext] = self::ALLOWED[$detectedMime];

        // ── GD re-encode (strip metadata / embedded payloads) ─────────────────
        /** @var \GdImage|false $image */
        $image = $loader($tmpPath);
        if ($image === false) {
            throw new AppException('GD failed to decode image.', 422);
        }
        if ($detectedMime === 'image/png') {
            imagesavealpha($image, true);
        }

        $filename = uuid() . '.' . $ext;

        // ── OSS path ──────────────────────────────────────────────────────────
        if ($this->oss->isConfigured()) {
            // Capture GD output ke buffer (PHP supports nested output buffers)
            ob_start();
            $saver($image);
            $buffer = ob_get_clean() ?: '';
            @imagedestroy($image); // deprecated no-op PHP 8.5+, suppress warning

            if ($buffer === '') {
                throw new AppException('GD failed to encode image to buffer.', 500);
            }

            $ossKey = self::OSS_PREFIX . $filename;
            $this->oss->upload($ossKey, $buffer, $detectedMime);

            return [
                'name' => $filename,
                'url'  => '/admin/v1/media/file/' . $filename,
                'key'  => $ossKey,
            ];
        }

        // ── Local fallback ────────────────────────────────────────────────────
        $destDir  = $this->appRoot . '/' . self::LOCAL_DIR;
        $destPath = $destDir . '/' . $filename;
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        $saved = $saver($image, $destPath);
        @imagedestroy($image); // deprecated no-op PHP 8.5+, suppress warning

        if (!$saved) {
            throw new AppException('Failed to save re-encoded image.', 500);
        }

        return [
            'name' => $filename,
            'url'  => self::PUBLIC_PATH . '/' . $filename,
            'key'  => 'editor/' . $filename,
        ];
    }

    public function delete(string $key): void
    {
        // OSS key: "media/editor/{filename}"
        if (str_starts_with($key, self::OSS_PREFIX)) {
            if (str_contains($key, '..')) {
                throw new ValidationAppException('Invalid media key.', ['key' => 'Invalid media key.']);
            }
            $this->oss->delete($key);
            return;
        }

        // Local key: "editor/{filename}"
        if (!str_starts_with($key, 'editor/') || str_contains($key, '..')) {
            throw new ValidationAppException('Invalid media key.', ['key' => 'Invalid media key.']);
        }
        $filename = substr($key, strlen('editor/'));
        if ($filename === '' || str_contains($filename, '/')) {
            throw new ValidationAppException('Invalid media key.', ['key' => 'Invalid media key.']);
        }
        $path = $this->appRoot . '/' . self::LOCAL_DIR . '/' . $filename;
        if (!file_exists($path)) {
            throw new AppException("Media file not found: {$key}", 404);
        }
        if (!unlink($path)) {
            throw new AppException("Failed to delete media file: {$key}", 500);
        }
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /** @return list<array{name:string,url:string,key:string}> */
    private function listFromOss(): array
    {
        $objects = $this->oss->listObjects(self::OSS_PREFIX);
        $result  = [];
        foreach ($objects as $obj) {
            $key  = (string)($obj['Key'] ?? '');
            $name = basename($key);
            // Skip folder marker (key === prefix atau berakhir '/')
            if ($key === self::OSS_PREFIX || str_ends_with($key, '/') || $name === '') {
                continue;
            }
            $result[] = [
                'name' => $name,
                'url'  => '/admin/v1/media/file/' . $name,
                'key'  => $key,
            ];
        }
        return $result;
    }

    /** @return list<array{name:string,url:string,key:string}> */
    private function listLocal(): array
    {
        $dir = $this->appRoot . '/' . self::LOCAL_DIR;
        if (!is_dir($dir)) {
            return [];
        }
        $files  = scandir($dir) ?: [];
        $result = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || is_dir($dir . '/' . $file)) {
                continue;
            }
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                continue;
            }
            $result[] = [
                'name' => $file,
                'url'  => self::PUBLIC_PATH . '/' . $file,
                'key'  => 'editor/' . $file,
            ];
        }
        return $result;
    }
}
