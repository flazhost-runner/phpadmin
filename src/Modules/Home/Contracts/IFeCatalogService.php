<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Home\Contracts;

/**
 * IFeCatalogService — contract for the opentailwind frontend template catalog.
 *
 * The implementation fetches the GitHub tree API once, caches 6h in-memory
 * and on disk (_catalog.json), and provides server-side pagination + HTML preview
 * with anti-SSRF validation (slug allowlist).
 */
interface IFeCatalogService
{
    /**
     * Return the full list of available templates (slug, name, category).
     *
     * @return list<array{slug:string,name:string,category:string}>
     */
    public function list(): array;

    /**
     * Paginate the catalog with optional name/category filters.
     *
     * @param  array<string,mixed> $filter  Keys: q_name, q_category, q_page, q_page_size.
     * @return array{
     *     items: list<array{slug:string,name:string,category:string}>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     last_page: int
     * }
     */
    public function paginate(array $filter): array;

    /**
     * Fetch the raw HTML for a single template preview.
     *
     * Anti-SSRF: only slugs present in the catalog allowlist are accepted.
     *
     * @throws \PHPAdmin\Core\Exceptions\AppException on unknown slug or fetch failure.
     */
    public function previewHtml(string $slug): string;
}
