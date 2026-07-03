<?php

/**
 * Admin sidebar layout partial.
 *
 * Expected variables (injected by controller):
 *   $setting     array<string,mixed>|null   app settings row
 *   $currentUser object|null
 */

declare(strict_types=1);

if (!function_exists('hasAccess')) {
    /**
     * Check if the currently authenticated user has access to a named route.
     *
     * Administrator role bypasses all permission checks.
     * Reads roles/permissions from $_SESSION set at login time.
     */
    function hasAccess(string $routeName, string $method = 'GET'): bool
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }
        // Administrator bypasses all checks
        $roles = $_SESSION['user_roles'] ?? [];
        if (in_array('Administrator', $roles, true)) {
            return true;
        }
        // Check permission list (stored at login)
        $permissions = $_SESSION['user_permissions'] ?? [];
        foreach ($permissions as $perm) {
            if (
                ($perm['name']       ?? '') === $routeName &&
                strtoupper($perm['method']      ?? 'GET') === strtoupper($method) &&
                ($perm['guard_name'] ?? 'web') === 'web'
            ) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('hasRole')) {
    /**
     * Check if the currently authenticated user has a given role.
     *
     * Reads roles from $_SESSION['user_roles'] set at login time.
     */
    function hasRole(string $roleName): bool
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }
        $roles = $_SESSION['user_roles'] ?? [];
        return in_array($roleName, $roles, true);
    }
}

if (!function_exists('sidebarNavActive')) {
    /**
     * Return ' active' CSS class suffix when the current URI starts with a segment.
     */
    function sidebarNavActive(string $seg): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return str_starts_with($uri, $seg) ? ' active' : '';
    }
}

$_settingArr      = is_array($setting ?? null) ? $setting : (is_object($setting ?? null) ? (array)$setting : []);
$_settingName     = (string)($_settingArr['name']      ?? 'Admin Panel');
$_settingLogo     = (string)($_settingArr['logo']      ?? '');
$_settingCopyright = (string)($_settingArr['copyright'] ?? '');
?>
<!-- Sidebar (Tailwind, themeable) -->
<aside id="tw-sidebar"
       class="sidebar-gradient text-white w-64 min-h-screen fixed top-0 left-0 z-40
              transform -translate-x-full md:translate-x-0 transition-transform duration-300 flex flex-col">

    <!-- Brand -->
    <a href="<?= route('admin.v1.dashboard.index') ?>"
       class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
        <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center overflow-hidden shrink-0">
            <?php if ($_settingLogo !== '') : ?>
                <img src="<?= e($_settingLogo) ?>" alt="logo" class="w-full h-full object-contain p-1">
            <?php else : ?>
                <i class="fas fa-chart-line text-xl"></i>
            <?php endif; ?>
        </div>
        <span class="text-lg font-bold truncate"><?= e($_settingName) ?></span>
    </a>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

        <a href="<?= route('admin.v1.dashboard.index') ?>"
           class="nav-link-tw flex items-center gap-3 px-4 py-3 rounded-lg font-medium<?= sidebarNavActive('/admin/v1/dashboard') ?>">
            <i class="fas fa-tachometer-alt w-5 text-center"></i>
            <span>Dashboard</span>
        </a>

        <?php if (hasAccess('admin.v1.components.index', 'GET')) : ?>
        <a href="<?= route('admin.v1.components.index') ?>"
           class="nav-link-tw flex items-center gap-3 px-4 py-3 rounded-lg font-medium<?= sidebarNavActive('/admin/v1/components') ?>">
            <i class="fas fa-cubes w-5 text-center"></i>
            <span>UI Components</span>
        </a>
        <?php endif; ?>

        <?php if (
            hasAccess('admin.v1.access.permission.index', 'GET') ||
            hasAccess('admin.v1.access.role.index', 'GET') ||
            hasAccess('admin.v1.access.user.index', 'GET') ||
            hasAccess('admin.v1.setting.index', 'GET')
) : ?>
        <p class="px-4 pt-5 pb-2 text-xs uppercase tracking-wider text-white/70 font-bold">Maintenance</p>

        <?php if (hasAccess('admin.v1.access.permission.index', 'GET')) : ?>
        <a href="<?= route('admin.v1.access.permission.index') ?>"
           class="nav-link-tw flex items-center gap-3 px-4 py-3 rounded-lg font-medium<?= sidebarNavActive('/admin/v1/access/permission') ?>">
            <i class="fas fa-key w-5 text-center"></i><span>Permission</span>
        </a>
        <?php endif; ?>

        <?php if (hasAccess('admin.v1.access.role.index', 'GET')) : ?>
        <a href="<?= route('admin.v1.access.role.index') ?>"
           class="nav-link-tw flex items-center gap-3 px-4 py-3 rounded-lg font-medium<?= sidebarNavActive('/admin/v1/access/role') ?>">
            <i class="fas fa-user-shield w-5 text-center"></i><span>Role</span>
        </a>
        <?php endif; ?>

        <?php if (hasAccess('admin.v1.access.user.index', 'GET')) : ?>
        <a href="<?= route('admin.v1.access.user.index') ?>"
           class="nav-link-tw flex items-center gap-3 px-4 py-3 rounded-lg font-medium<?= sidebarNavActive('/admin/v1/access/user') ?>">
            <i class="fas fa-users w-5 text-center"></i><span>User</span>
        </a>
        <?php endif; ?>

        <?php if (hasAccess('admin.v1.setting.index', 'GET')) : ?>
        <a href="<?= route('admin.v1.setting.index') ?>"
           class="nav-link-tw flex items-center gap-3 px-4 py-3 rounded-lg font-medium<?= sidebarNavActive('/admin/v1/setting') ?>">
            <i class="fas fa-cog w-5 text-center"></i><span>Setting</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

    </nav>

    <div class="px-6 py-4 text-xs text-white/40 border-t border-white/10">
        <?= e($_settingCopyright) ?>
    </div>
</aside>

<!-- Overlay (mobile) -->
<div id="tw-sidebar-overlay" class="fixed inset-0 bg-black/40 z-30 hidden md:hidden"></div>
<!-- End Sidebar -->
