<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Dashboard\Services;

use Illuminate\Database\Capsule\Manager as DB;
use PHPAdmin\Modules\Dashboard\Contracts\IDashboardService;

/**
 * DashboardService — reads aggregate counts from DB, falls back to zero when
 * the target table does not yet exist (e.g. during first-run / migration).
 */
class DashboardService implements IDashboardService
{
    /**
     * @return array{users: int, roles: int, permissions: int}
     */
    public function getStats(): array
    {
        $count = static function (string $table): int {
            try {
                return (int) DB::table($table)->count();
            } catch (\Throwable) {
                return 0;
            }
        };

        return [
            'users'       => $count('users'),
            'roles'       => $count('roles'),
            'permissions' => $count('permissions'),
        ];
    }

    /**
     * @return list<array{icon: string, bg: string, iconColor: string, title: string, subtitle: string}>
     */
    public function getRecentActivities(): array
    {
        return [
            [
                'icon'      => 'fa-user',
                'bg'        => 'var(--theme-light)',
                'iconColor' => 'var(--primary)',
                'title'     => 'New user registered',
                'subtitle'  => 'john.doe@example.com — 2 minutes ago',
            ],
            [
                'icon'      => 'fa-shopping-cart',
                'bg'        => '#22c55e',
                'iconColor' => '#ffffff',
                'title'     => 'New order placed',
                'subtitle'  => 'Order #12345 — $299.99 — 15 minutes ago',
            ],
            [
                'icon'      => 'fa-exclamation',
                'bg'        => '#f59e0b',
                'iconColor' => '#ffffff',
                'title'     => 'Low stock alert',
                'subtitle'  => 'Product ABC — Only 5 items left — 1 hour ago',
            ],
            [
                'icon'      => 'fa-star',
                'bg'        => '#8b5cf6',
                'iconColor' => '#ffffff',
                'title'     => 'New review received',
                'subtitle'  => '5 stars for Product XYZ — 2 hours ago',
            ],
        ];
    }
}
