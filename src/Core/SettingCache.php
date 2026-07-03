<?php

declare(strict_types=1);

namespace PHPAdmin\Core;

/**
 * Simple in-memory (per-request) cache for application settings with a 60-second TTL.
 *
 * Because PHP shares nothing between requests, this mainly guards against
 * multiple DB reads within a single request lifecycle.
 */
class SettingCache
{
    private const TTL = 60; // seconds

    /** @var array<string, mixed>|null */
    private static ?array $data = null;

    private static ?int $setAt = null;

    /**
     * Retrieve cached settings, or null if empty / expired.
     *
     * @return array<string, mixed>|null
     */
    public static function get(): ?array
    {
        if (self::$data === null || self::$setAt === null) {
            return null;
        }

        if ((time() - self::$setAt) > self::TTL) {
            self::invalidate();
            return null;
        }

        return self::$data;
    }

    /**
     * Store settings in the cache.
     *
     * @param array<string, mixed> $data
     */
    public static function set(array $data): void
    {
        self::$data  = $data;
        self::$setAt = time();
    }

    /**
     * Clear the cached settings.
     */
    public static function invalidate(): void
    {
        self::$data  = null;
        self::$setAt = null;
    }
}
