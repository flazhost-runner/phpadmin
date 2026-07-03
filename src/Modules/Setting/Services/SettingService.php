<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Setting\Services;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPAdmin\Core\Exceptions\AppException;
use PHPAdmin\Modules\Setting\Contracts\ISettingService;

/**
 * SettingService — reads and writes the single settings row,
 * maintains the FE-template catalog, and serves preview HTML.
 *
 * Catalog strategy (mirrors NodeAdmin FeCatalogService):
 *   1. In-memory static memo (per-request, cleared on restart).
 *   2. Disk cache: public/fe/templates/_catalog.json (TTL: 6 h).
 *   3. GitHub tree API fetch (20 s timeout) on total miss.
 *   4. Curated fallback list when GitHub is unreachable.
 */
class SettingService implements ISettingService
{
    // ─── Constants ────────────────────────────────────────────────────────────

    private const SLUG_RE = '/^([a-z]+(?:-[a-z]+)*)-(\d{3})-([a-z0-9-]+)$/';

    private const FE_BASE_URL =
        'https://raw.githubusercontent.com/lindoai/opentailwind/master/landings';

    private const FE_TREE_URL =
        'https://api.github.com/repos/lindoai/opentailwind/git/trees/master?recursive=1';

    private const CATALOG_FILE   = 'public/fe/templates/_catalog.json';
    private const TEMPLATE_DIR   = 'public/fe/templates';
    private const CATALOG_TTL    = 6 * 3600; // 6 hours in seconds
    private const PREVIEW_TIMEOUT = 10;       // seconds

    /** Curated fallback when GitHub is unreachable. */
    private const FALLBACK_CATALOG = [
        [
            'slug'     => 'agency-consulting-002-creative-agency',
            'name'     => 'Creative Agency',
            'category' => 'Agency Consulting',
        ],
        [
            'slug'     => 'agency-consulting-001-digital-marketing-agency',
            'name'     => 'Digital Marketing Agency',
            'category' => 'Agency Consulting',
        ],
        [
            'slug'     => 'technology-saas-001-hero-focused-conversion-page',
            'name'     => 'Hero Focused Conversion Page',
            'category' => 'Technology',
        ],
        [
            'slug'     => 'technology-saas-002-feature-rich-multi-section',
            'name'     => 'Feature Rich Multi Section',
            'category' => 'Technology',
        ],
        [
            'slug'     => 'ecommerce-retail-001-fashion-boutique',
            'name'     => 'Fashion Boutique',
            'category' => 'Ecommerce Retail',
        ],
        [
            'slug'     => 'ecommerce-retail-002-luxury-fashion-brand',
            'name'     => 'Luxury Fashion Brand',
            'category' => 'Ecommerce Retail',
        ],
        [
            'slug'     => 'portfolio-creative-001-creative-portfolio',
            'name'     => 'Creative Portfolio',
            'category' => 'Portfolio Creative',
        ],
        [
            'slug'     => 'portfolio-creative-002-minimal-portfolio',
            'name'     => 'Minimal Portfolio',
            'category' => 'Portfolio Creative',
        ],
        [
            'slug'     => 'healthcare-medical-001-clinic-services',
            'name'     => 'Clinic Services',
            'category' => 'Healthcare Medical',
        ],
        [
            'slug'     => 'education-learning-001-online-course-platform',
            'name'     => 'Online Course Platform',
            'category' => 'Education Learning',
        ],
        [
            'slug'     => 'restaurant-food-001-fine-dining',
            'name'     => 'Fine Dining',
            'category' => 'Restaurant Food',
        ],
        [
            'slug'     => 'real-estate-property-001-luxury-properties',
            'name'     => 'Luxury Properties',
            'category' => 'Real Estate Property',
        ],
        [
            'slug'     => 'nonprofit-charity-001-fundraising',
            'name'     => 'Fundraising',
            'category' => 'Nonprofit Charity',
        ],
        [
            'slug'     => 'travel-tourism-001-travel-agency',
            'name'     => 'Travel Agency',
            'category' => 'Travel Tourism',
        ],
        [
            'slug'     => 'fitness-wellness-001-gym-studio',
            'name'     => 'Gym Studio',
            'category' => 'Fitness Wellness',
        ],
    ];

    // ─── In-memory catalog memo (static: survives within the request) ─────────

    /** @var array{at:int,data:list<array{slug:string,name:string,category:string}>}|null */
    private static ?array $catalogMemo = null;

    // ─── Default setting row values ────────────────────────────────────────────

    /**
     * @return array<string,mixed>
     */
    private function defaults(): array
    {
        return [
            'id'          => null,
            'initial'     => '',
            'name'        => '',
            'description' => '',
            'icon'        => '',
            'logo'        => '',
            'favicon'     => '',
            'login_image' => '',
            'phone'       => '',
            'address'     => '',
            'email'       => '',
            'copyright'   => '',
            'theme'       => 'Blue',
            'fe_template' => 'agency-consulting-002-creative-agency',
            'created_by'  => null,
            'updated_by'  => null,
        ];
    }

    // ─── ISettingService ──────────────────────────────────────────────────────

    public function get(): array
    {
        $cached = SettingCache::get();
        if ($cached !== null) {
            return $cached;
        }

        $row  = Capsule::table('settings')->first();
        $data = $row !== null ? (array)$row : $this->defaults();

        SettingCache::set($data);
        return $data;
    }

    public function update(array $data): array
    {
        // Sanitise rich-text description — prevent stored XSS.
        if (isset($data['description']) && is_string($data['description'])) {
            $data['description'] = $this->cleanRichText($data['description']);
        }

        // Remove null / empty-string values so we never overwrite existing DB
        // fields with blanks when the form field was left empty (e.g. file fields
        // not re-uploaded).
        $data = array_filter(
            $data,
            static fn(mixed $v): bool => $v !== null && $v !== ''
        );

        $row = Capsule::table('settings')->first();

        if ($row === null) {
            // First-time insert — generate a UUID primary key.
            $data['id']         = $this->generateId();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            Capsule::table('settings')->insert($data);
        } else {
            $data['updated_at'] = date('Y-m-d H:i:s');
            Capsule::table('settings')->where('id', $row->id)->update($data);
        }

        SettingCache::invalidate();
        return $this->get();
    }

    public function previewTemplate(string $slug): string
    {
        // 1. Validate slug format.
        if (preg_match(self::SLUG_RE, $slug) !== 1) {
            throw new AppException("Slug tidak valid: {$slug}", 400);
        }

        // 2. Serve from local disk cache if present.
        $local = $this->localHtmlPath($slug);
        if (is_file($local)) {
            $html = (string)file_get_contents($local);
            if (stripos($html, '</html>') !== false) {
                return $html;
            }
        }

        // 3. Fetch upstream with timeout.
        $url  = self::FE_BASE_URL . '/' . $slug . '.html';
        $html = $this->httpGet($url, self::PREVIEW_TIMEOUT);

        if ($html === null || stripos($html, '</html>') === false) {
            throw new AppException("Gagal mengambil preview template: {$slug}", 502);
        }

        // 4. Persist locally for subsequent requests.
        $this->ensureDir(dirname($local));
        file_put_contents($local, $html);

        return $html;
    }

    public function catalogPaginate(array $filter, string $pinSlug = ''): array
    {
        $all      = $this->catalogList();
        $qName    = strtolower(trim((string)($filter['q_name']     ?? '')));
        $qCat     = trim((string)($filter['q_category']            ?? ''));
        $pageSize = max(1, (int)($filter['q_page_size']            ?? 12));
        $page     = max(1, (int)($filter['q_page']                 ?? 1));

        // Filter.
        $filtered = array_values(array_filter(
            $all,
            static function (array $t) use ($qName, $qCat): bool {
                $okName = $qName === ''
                    || str_contains(strtolower($t['name']), $qName)
                    || str_contains(strtolower($t['slug']), $qName);
                $okCat = $qCat === '' || $t['category'] === $qCat;
                return $okName && $okCat;
            }
        ));

        // Pin active template at position 0 (visible on page 1).
        if ($pinSlug !== '') {
            $idx = array_search(
                $pinSlug,
                array_column($filtered, 'slug'),
                true
            );
            if ($idx !== false && $idx > 0) {
                $pinned = array_splice($filtered, (int)$idx, 1);
                array_unshift($filtered, $pinned[0]);
            }
        }

        $total    = count($filtered);
        $start    = ($page - 1) * $pageSize;
        $items    = array_values(array_slice($filtered, $start, $pageSize));
        $lastPage = (int)ceil($total / max(1, $pageSize));

        return [
            'datas'     => $items,
            'paginate_data' => [
                'total_data'   => $total,
                'page_size'    => $pageSize,
                'current_page' => $page,
                'total_page'   => $lastPage,
            ],
        ];
    }

    public function catalogCategories(): array
    {
        $all  = $this->catalogList();
        $cats = array_unique(array_column($all, 'category'));
        sort($cats);
        return $cats;
    }

    // ─── Private: catalog ─────────────────────────────────────────────────────

    /**
     * Return the full catalog list, loading from memo → disk → upstream.
     *
     * @return list<array{slug:string,name:string,category:string}>
     */
    private function catalogList(): array
    {
        // 1. In-memory memo (same request).
        if (
            self::$catalogMemo !== null
            && (time() - self::$catalogMemo['at']) < self::CATALOG_TTL
        ) {
            return self::$catalogMemo['data'];
        }

        // 2. Disk cache.
        $disk = $this->readDiskCatalog();
        if ($disk !== null) {
            self::$catalogMemo = ['at' => time(), 'data' => $disk];
            return $disk;
        }

        // 3. Fetch GitHub tree (20 s timeout, longgar untuk response recursive).
        $data = $this->fetchCatalogFromGitHub();

        if ($data === null || count($data) === 0) {
            // 4. Curated fallback — always functional offline.
            $data = self::FALLBACK_CATALOG;
        } else {
            $this->writeDiskCatalog($data);
        }

        self::$catalogMemo = ['at' => time(), 'data' => $data];
        return $data;
    }

    /**
     * @return list<array{slug:string,name:string,category:string}>|null
     */
    private function readDiskCatalog(): ?array
    {
        $path = $this->appRoot() . '/' . self::CATALOG_FILE;
        if (!is_file($path)) {
            return null;
        }
        // Honor TTL: ignore stale disk cache.
        if ((time() - (int)filemtime($path)) > self::CATALOG_TTL) {
            return null;
        }
        try {
            $raw  = (string)file_get_contents($path);
            $data = json_decode($raw, true);
            if (is_array($data) && count($data) > 0) {
                /** @var list<array{slug:string,name:string,category:string}> $data */
                return $data;
            }
        } catch (\Throwable) {
            // ignore
        }
        return null;
    }

    /**
     * @param list<array{slug:string,name:string,category:string}> $data
     */
    private function writeDiskCatalog(array $data): void
    {
        $path = $this->appRoot() . '/' . self::CATALOG_FILE;
        $this->ensureDir(dirname($path));
        file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Fetch GitHub tree, parse, sort and return all template slugs.
     *
     * @return list<array{slug:string,name:string,category:string}>|null
     */
    private function fetchCatalogFromGitHub(): ?array
    {
        $raw = $this->httpGet(self::FE_TREE_URL, 20, [
            'Accept: application/vnd.github+json',
        ]);
        if ($raw === null) {
            return null;
        }
        try {
            $body = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $tree = $body['tree'] ?? [];
        } catch (\Throwable) {
            return null;
        }
        if (!is_array($tree)) {
            return null;
        }

        $items = [];
        foreach ($tree as $node) {
            if (
                isset($node['type'], $node['path'])
                && $node['type'] === 'blob'
                && is_string($node['path'])
                && str_starts_with($node['path'], 'landings/')
                && str_ends_with($node['path'], '.html')
            ) {
                $slug = substr($node['path'], strlen('landings/'), -strlen('.html'));
                $items[] = $this->deriveTemplate($slug);
            }
        }

        usort($items, static fn($a, $b) =>
            $a['category'] <=> $b['category'] ?: $a['name'] <=> $b['name']);

        return $items;
    }

    /**
     * Derive display metadata from an opentailwind slug.
     *
     * Pattern: {category}-{NNN}-{name}  e.g. agency-consulting-002-creative-agency
     *
     * @return array{slug:string,name:string,category:string}
     */
    private function deriveTemplate(string $slug): array
    {
        if (preg_match(self::SLUG_RE, $slug, $m) === 1) {
            return [
                'slug'     => $slug,
                'name'     => $this->titleize((string)$m[3]),
                'category' => $this->titleize((string)$m[1]),
            ];
        }
        return [
            'slug'     => $slug,
            'name'     => $this->titleize($slug),
            'category' => 'Other',
        ];
    }

    // ─── Private: helpers ─────────────────────────────────────────────────────

    /**
     * Sanitise Trumbowyg rich-text output — strip all but the whitelisted tags.
     */
    private function cleanRichText(string $html): string
    {
        $allowed = '<p><br><b><strong><i><em><del><sup><sub>'
            . '<a><ul><ol><li><h1><h2><h3><h4><h5><h6>'
            . '<hr><blockquote><div><span>';
        return strip_tags($html, $allowed);
    }

    /**
     * Title-case from hyphenated string: "creative-agency" → "Creative Agency".
     */
    private function titleize(string $s): string
    {
        return implode(' ', array_map('ucfirst', explode('-', $s)));
    }

    /**
     * Absolute path to the cached HTML for one template slug.
     */
    private function localHtmlPath(string $slug): string
    {
        return $this->appRoot() . '/' . self::TEMPLATE_DIR . '/' . $slug . '.html';
    }

    /**
     * App root — same constant used by public/index.php.
     */
    private function appRoot(): string
    {
        return defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4);
    }

    /**
     * Ensure a directory exists, creating it recursively if necessary.
     */
    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * HTTP GET via file_get_contents + stream context.
     * Returns the response body on 2xx, null on network error or non-2xx.
     *
     * @param  string[] $extraHeaders  Raw header lines, e.g. ['Accept: application/json']
     */
    private function httpGet(string $url, int $timeoutSeconds = 10, array $extraHeaders = []): ?string
    {
        $headers = array_merge(['User-Agent: PHPAdmin/1.0'], $extraHeaders);

        $ctx = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'header'          => implode("\r\n", $headers),
                'timeout'         => (float)$timeoutSeconds,
                'ignore_errors'   => true,
                'follow_location' => true,
                'max_redirects'   => 3,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            return null;
        }

        $status = 0;
        // PHP 8.3-kompatibel: $http_response_header diisi otomatis oleh
        // file_get_contents dengan wrapper http (http_get_last_response_headers()
        // baru ada di PHP 8.5).
        $responseHeaders = $http_response_header;
        if (!empty($responseHeaders)) {
            if (preg_match('/HTTP\/\S+ (\d{3})/', $responseHeaders[0], $m)) {
                $status = (int)$m[1];
            }
        }

        if ($status < 200 || $status >= 300) {
            return null;
        }

        return $body;
    }

    /**
     * Generate a UUID v4 string (same logic as the global uuid() helper).
     */
    private function generateId(): string
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(substr($bytes, 0, 4)),
            bin2hex(substr($bytes, 4, 2)),
            bin2hex(substr($bytes, 6, 2)),
            bin2hex(substr($bytes, 8, 2)),
            bin2hex(substr($bytes, 10, 6))
        );
    }
}
