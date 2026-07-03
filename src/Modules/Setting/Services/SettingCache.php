<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Setting\Services;

use PHPAdmin\Core\SettingCache as CoreSettingCache;

/**
 * Module-level adapter for the application setting row cache.
 *
 * Delegates unconditionally to Core\SettingCache so that all controllers
 * (Access, Auth, etc.) that resolve theme / brand data stay in sync with
 * every write made by SettingService.
 *
 * TTL: 60 seconds — enforced by Core\SettingCache.
 */
class SettingCache
{
    /**
     * Retrieve cached settings, or null when empty / expired.
     *
     * @return array<string,mixed>|null
     */
    public static function get(): ?array
    {
        return CoreSettingCache::get();
    }

    /**
     * Store settings in the cache.
     *
     * @param array<string,mixed> $data
     */
    public static function set(array $data): void
    {
        CoreSettingCache::set($data);
    }

    /**
     * Clear the cached settings (call after every successful update).
     */
    public static function invalidate(): void
    {
        CoreSettingCache::invalidate();
    }
}
