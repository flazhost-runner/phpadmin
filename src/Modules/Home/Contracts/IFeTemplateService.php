<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Home\Contracts;

interface IFeTemplateService
{
    /**
     * Ensure HTML for $slug is available locally.
     * Attempts download if not cached; throws AppException on failure.
     */
    public function ensure(string $slug): void;

    /** Raw HTML of the active template, or null to use the native view. */
    public function getActiveHtml(): ?string;

    /** Active template slug from DB, or null when using the native view. */
    public function activeSlug(): ?string;

    /** True if the template HTML file exists in local cache. */
    public function isCached(string $slug): bool;

    /** List of slugs that are cached locally and ready to use. */
    public function cachedSlugs(): array;
}
