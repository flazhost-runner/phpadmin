<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Dashboard\Contracts;

/**
 * IDashboardService — contract for dashboard statistics and activity data.
 */
interface IDashboardService
{
    /**
     * Return aggregate counts for the main stat cards.
     *
     * @return array{users: int, roles: int, permissions: int}
     */
    public function getStats(): array;

    /**
     * Return recent activity feed items.
     *
     * @return list<array{icon: string, bg: string, iconColor: string, title: string, subtitle: string}>
     */
    public function getRecentActivities(): array;
}
