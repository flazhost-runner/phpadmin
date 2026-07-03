<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Home\Services;

use PHPAdmin\Core\Exceptions\AppException;
use PHPAdmin\Modules\Home\Contracts\IFeCatalogService;

/**
 * FeCatalogService — fetches the opentailwind GitHub template tree.
 *
 * Cache strategy:
 *   1. In-memory (per-process) — 6h TTL.
 *   2. Disk (_catalog.json in storage/fe-cache/) — survives restarts.
 *   3. Fallback: empty list (graceful degradation when GitHub is unreachable).
 *
 * Preview HTML: validates slug against the allowlist before fetching upstream
 * (anti-SSRF). Raw GitHub CDN URL is the only external source allowed.
 */
class FeCatalogService implements IFeCatalogService
{
    private const TREE_URL     = 'https://api.github.com/repos/opentailwind/templates/git/trees/main?recursive=1';
    private const BASE_URL     = 'https://raw.githubusercontent.com/opentailwind/templates/main/landings';
    private const CACHE_FILE   = 'storage/fe-cache/_catalog.json';
    private const CACHE_TTL    = 6 * 3600; // 6 hours in seconds
    private const TREE_TIMEOUT = 20;       // seconds
    private const PREV_TIMEOUT = 8;        // seconds

    /** @var array{at:int,data:list<array{slug:string,name:string,category:string}>}|null */
    private static ?array $memo = null;

    private string $appRoot;

    public function __construct(string $appRoot = '')
    {
        $this->appRoot = $appRoot !== '' ? $appRoot : (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4));
    }

    // ─── IFeCatalogService ────────────────────────────────────────────────────

    public function list(): array
    {
        // 1) In-memory (per-request/per-process)
        if (self::$memo !== null && (time() - self::$memo['at']) < self::CACHE_TTL) {
            return self::$memo['data'];
        }

        // 2) Disk cache
        $disk = $this->readDisk();
        if ($disk !== null) {
            self::$memo = ['at' => time(), 'data' => $disk];
            return $disk;
        }

        // 3) Fetch from GitHub
        $data = $this->fetchTree();
        self::$memo = ['at' => time(), 'data' => $data];
        if ($data !== []) {
            $this->writeDisk($data);
        }

        return $data;
    }

    public function paginate(array $filter): array
    {
        $all      = $this->list();
        $qName    = strtolower(trim((string)($filter['q_name']     ?? '')));
        $qCat     = trim((string)($filter['q_category'] ?? ''));
        $perPage  = max(1, (int)($filter['q_page_size'] ?? 12));
        $page     = max(1, (int)($filter['q_page']      ?? 1));

        $filtered = array_values(array_filter($all, static function (array $t) use ($qName, $qCat): bool {
            $okName = $qName === ''
                || str_contains(strtolower($t['slug']), $qName)
                || str_contains(strtolower($t['name']), $qName);
            $okCat  = $qCat  === '' || $t['category'] === $qCat;
            return $okName && $okCat;
        }));

        $total    = count($filtered);
        $items    = array_slice($filtered, ($page - 1) * $perPage, $perPage);

        return [
            'items'     => $items,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    public function previewHtml(string $slug): string
    {
        // Anti-SSRF: slug must be in the catalog allowlist
        $all = $this->list();
        $slugs = array_column($all, 'slug');
        if (!in_array($slug, $slugs, true)) {
            throw new AppException("Unknown template slug: {$slug}", 400);
        }

        $url = self::BASE_URL . '/' . rawurlencode($slug) . '.html';

        $ctx = stream_context_create([
            'http' => [
                'timeout'        => self::PREV_TIMEOUT,
                'ignore_errors'  => true,
                'header'         => "User-Agent: PHPAdmin/1.0\r\n",
            ],
        ]);

        $html = @file_get_contents($url, false, $ctx);
        if ($html === false || !str_contains(strtolower($html), '</html>')) {
            throw new AppException("Failed to fetch template preview for: {$slug}", 502);
        }

        return $html;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * @return list<array{slug:string,name:string,category:string}>
     */
    private function fetchTree(): array
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout'       => self::TREE_TIMEOUT,
                'ignore_errors' => true,
                'header'        => "User-Agent: PHPAdmin/1.0\r\nAccept: application/vnd.github+json\r\n",
            ],
        ]);

        $raw = @file_get_contents(self::TREE_URL, false, $ctx);
        if ($raw === false) {
            return [];
        }

        /** @var array<string,mixed>|null $body */
        $body = json_decode($raw, true);
        if (!is_array($body) || !isset($body['tree']) || !is_array($body['tree'])) {
            return [];
        }

        return $this->parseTree($body['tree']);
    }

    /**
     * @param  array<mixed> $tree
     * @return list<array{slug:string,name:string,category:string}>
     */
    private function parseTree(array $tree): array
    {
        $items = [];
        foreach ($tree as $node) {
            if (!is_array($node)) {
                continue;
            }
            $path = (string)($node['path'] ?? '');
            $type = (string)($node['type'] ?? '');
            if ($type !== 'blob' || !str_starts_with($path, 'landings/') || !str_ends_with($path, '.html')) {
                continue;
            }
            $slug = substr($path, strlen('landings/'), -strlen('.html'));
            $parts = explode('-', $slug, 2);
            $items[] = [
                'slug'     => $slug,
                'name'     => ucwords(str_replace('-', ' ', $slug)),
                'category' => ucwords(str_replace('-', ' ', $parts[0] ?? 'other')),
            ];
        }

        usort($items, static fn($a, $b) => $a['category'] <=> $b['category'] ?: $a['name'] <=> $b['name']);

        /** @var list<array{slug:string,name:string,category:string}> $items */
        return $items;
    }

    /**
     * @return list<array{slug:string,name:string,category:string}>|null
     */
    private function readDisk(): ?array
    {
        $file = $this->appRoot . '/' . self::CACHE_FILE;
        if (!file_exists($file)) {
            return null;
        }
        // Check file age against TTL
        if ((time() - (int)filemtime($file)) > self::CACHE_TTL) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) && count($data) > 0 ? $data : null;
    }

    /**
     * @param list<array{slug:string,name:string,category:string}> $data
     */
    private function writeDisk(array $data): void
    {
        $file = $this->appRoot . '/' . self::CACHE_FILE;
        $dir  = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
