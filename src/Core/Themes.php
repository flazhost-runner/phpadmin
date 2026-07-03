<?php

declare(strict_types=1);

namespace PHPAdmin\Core;

/**
 * Admin UI theme definitions — 5 standard themes matching NodeAdmin.
 */
class Themes
{
    /**
     * @var array<string, array{name: string, primary: string, secondary: string, light: string, dark: string}>
     */
    public const THEMES = [
        'Blue' => [
            'name'      => 'Blue',
            'primary'   => '#3B82F6',
            'secondary' => '#60A5FA',
            'light'     => '#EFF6FF',
            'dark'      => '#1E40AF',
        ],
        'Purple' => [
            'name'      => 'Purple',
            'primary'   => '#8B5CF6',
            'secondary' => '#A78BFA',
            'light'     => '#F5F3FF',
            'dark'      => '#5B21B6',
        ],
        'Green' => [
            'name'      => 'Green',
            'primary'   => '#10B981',
            'secondary' => '#34D399',
            'light'     => '#ECFDF5',
            'dark'      => '#065F46',
        ],
        'Orange' => [
            'name'      => 'Orange',
            'primary'   => '#F59E0B',
            'secondary' => '#FCD34D',
            'light'     => '#FFFBEB',
            'dark'      => '#92400E',
        ],
        'Red' => [
            'name'      => 'Red',
            'primary'   => '#EF4444',
            'secondary' => '#F87171',
            'light'     => '#FEF2F2',
            'dark'      => '#991B1B',
        ],
    ];

    /**
     * Get a single theme by name. Returns Blue default for unknown/empty names.
     *
     * @return array{name: string, primary: string, secondary: string, light: string, dark: string}
     */
    public static function get(string $name): array
    {
        return self::THEMES[$name] ?? self::THEMES['Blue'];
    }

    /**
     * @return array<string, array{name: string, primary: string, secondary: string, light: string, dark: string}>
     */
    public static function all(): array
    {
        return self::THEMES;
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::THEMES);
    }
}
