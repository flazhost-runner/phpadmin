<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Home\Services;

use PHPAdmin\Core\Exceptions\AppException;
use PHPAdmin\Modules\Home\Contracts\IFeCatalogService;
use PHPAdmin\Modules\Home\Contracts\IFeTemplateService;
use PHPAdmin\Modules\Setting\Contracts\ISettingService;

class FeTemplateService implements IFeTemplateService
{
    private const BASE_URL      = 'https://raw.githubusercontent.com/opentailwind/templates/main/landings';
    private const CACHE_DIR     = 'public/fe/templates';
    private const FETCH_TIMEOUT = 15;

    private string $appRoot;

    public function __construct(
        private readonly IFeCatalogService $catalog,
        private readonly ISettingService $settingService,
        string $appRoot = ''
    ) {
        $this->appRoot = $appRoot !== '' ? $appRoot : (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4));
    }

    public function ensure(string $slug): void
    {
        $file = $this->localPath($slug);

        if (file_exists($file)) {
            return;
        }

        // Anti-SSRF: slug must exist in catalog
        $catalog = $this->catalog->list();
        $slugs   = array_column($catalog, 'slug');
        if (!in_array($slug, $slugs, true)) {
            throw new AppException("Template '{$slug}' tidak dikenali.", 400);
        }

        // Attempt download
        $url  = self::BASE_URL . '/' . rawurlencode($slug) . '.html';
        $ctx  = stream_context_create([
            'http' => [
                'timeout'       => self::FETCH_TIMEOUT,
                'ignore_errors' => true,
                'header'        => "User-Agent: PHPAdmin/1.0\r\n",
            ],
        ]);

        $html = @file_get_contents($url, false, $ctx);
        if ($html === false || !str_contains(strtolower($html), '</html>')) {
            throw new AppException(
                "Template '{$slug}' belum tersedia secara lokal dan gagal didownload. " .
                "Pilih template yang sudah tersedia (ditandai centang hijau).",
                502
            );
        }

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, $html);
    }

    public function getActiveHtml(): ?string
    {
        $slug = $this->activeSlug();
        if ($slug === null) {
            return null;
        }

        $file = $this->localPath($slug);
        if (!file_exists($file)) {
            return null; // template tidak ada lokal — tampilkan native view
        }

        $html = @file_get_contents($file);
        return ($html !== false && str_contains(strtolower($html), '</html>')) ? $html : null;
    }

    public function activeSlug(): ?string
    {
        $setting = $this->settingService->get();
        $slug    = trim((string)($setting['fe_template'] ?? ''));
        return ($slug === '' || $slug === 'default') ? null : $slug;
    }

    /** True jika HTML file sudah ada di lokal cache. */
    public function isCached(string $slug): bool
    {
        return file_exists($this->localPath($slug));
    }

    /** Daftar slug yang sudah ter-cache secara lokal. */
    public function cachedSlugs(): array
    {
        $dir   = $this->appRoot . '/' . self::CACHE_DIR;
        $files = glob($dir . '/*.html') ?: [];
        return array_map(
            static fn(string $f): string => basename($f, '.html'),
            array_filter($files, static fn(string $f): bool => basename($f) !== '_catalog.json')
        );
    }

    private function localPath(string $slug): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        return $this->appRoot . '/' . self::CACHE_DIR . '/' . $safe . '.html';
    }
}
