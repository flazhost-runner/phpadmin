<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Setting\Contracts;

interface ISettingService
{
    /**
     * Read the first settings row from the DB (cached 60 s via SettingCache).
     * Returns an assoc array of column values, or scalar defaults when the
     * table is empty.
     *
     * @return array<string,mixed>
     */
    public function get(): array;

    /**
     * Sanitise, merge, and persist the settings row; invalidates the cache.
     *
     * The caller is responsible for saving uploaded files and passing their
     * relative public paths (e.g. 'uploads/setting/xxx.jpg') via $data keys
     * icon, logo, login_image.  The service sanitises description with
     * strip_tags (rich-text whitelist) before saving.
     *
     * @param  array<string,mixed> $data
     * @return array<string,mixed> The saved row (re-fetched fresh).
     */
    public function update(array $data): array;

    /**
     * Return the raw HTML of one opentailwind landing template for use as a
     * thumbnail / modal preview.
     *
     * Validates the slug against the opentailwind pattern:
     *   ^([a-z]+(?:-[a-z]+)*)-(\d{3})-([a-z0-9-]+)$
     *
     * Checks a local disk cache first (public/fe/templates/{slug}.html).
     * Fetches from upstream with a 10-second cURL timeout on miss and caches
     * the result to disk.
     *
     * @throws \PHPAdmin\Core\Exceptions\AppException  on invalid slug or HTTP error.
     */
    public function previewTemplate(string $slug): string;

    /**
     * Server-side paginated + filtered list of FE catalog templates.
     *
     * Accepted filter keys: q_name, q_category, q_page (default 1),
     * q_page_size (default 12).
     *
     * When pinSlug is non-empty and present in the filtered set, that template
     * is moved to position 0 so the current selection is always on page 1.
     *
     * @param  array<string,mixed> $filter
     * @param  string              $pinSlug  Active template slug to pin at top.
     * @return array{
     *   datas: list<array{slug:string,name:string,category:string}>,
     *   paginate_data: array{total_data:int,page_size:int,current_page:int,total_page:int}
     * }
     */
    public function catalogPaginate(array $filter, string $pinSlug = ''): array;

    /**
     * Sorted unique list of template categories present in the FE catalog.
     *
     * @return list<string>
     */
    public function catalogCategories(): array;
}
