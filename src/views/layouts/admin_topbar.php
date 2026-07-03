<?php

/**
 * Admin topbar layout partial.
 *
 * Expected variables (injected by controller):
 *   $currentUser  object|array|null   logged-in user
 *   $_csrf        string              CSRF token
 */

declare(strict_types=1);

if (is_object($currentUser ?? null)) {
    $_userName    = (string)($currentUser->name    ?? 'User');
    $_userPicture = (string)($currentUser->picture ?? '');
} elseif (is_array($currentUser ?? null)) {
    $_userName    = (string)($currentUser['name']    ?? 'User');
    $_userPicture = (string)($currentUser['picture'] ?? '');
} else {
    $_userName    = 'User';
    $_userPicture = '';
}
?>
<!-- Topbar (Tailwind, themeable) -->
<header class="tw-card !rounded-none md:!rounded-xl bg-white px-4 md:px-6 py-3 mb-6
               flex items-center justify-between sticky top-0 z-20 shadow-sm">

    <div class="flex items-center gap-3">
        <!-- Mobile hamburger -->
        <button id="tw-sidebar-toggle" class="md:hidden text-2xl text-primary-tw" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>
        <!-- Home icon -->
        <a href="<?= route('admin.v1.dashboard.index') ?>" class="text-primary-tw text-xl">
            <i class="fas fa-home"></i>
        </a>
    </div>

    <!-- User dropdown — avatar image if set, initials-box fallback, gear icon.
         Logout via hidden POST form (more secure than GET link). -->
    <div class="dropdown">
        <a class="flex items-center gap-2 no-underline cursor-pointer" href="#" data-toggle-dd>
            <span class="hidden lg:inline text-gray-500">Welcome, <?= e($_userName) ?></span>
            <?php if ($_userPicture !== '') : ?>
            <img class="rounded-full"
                 style="width:38px;height:38px;object-fit:cover"
                 src="<?= e($_userPicture) ?>"
                 alt="user">
            <?php else : ?>
            <span class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                <i class="fas fa-user"></i>
            </span>
            <?php endif; ?>
            <i class="bi bi-gear" style="color:var(--primary)"></i>
        </a>

        <div class="dropdown-menu">
            <a class="dropdown-item" href="<?= route('admin.v1.profile.index') ?>">
                <i class="fas fa-user fa-fw"></i> Profile
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item"
               href="#"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fas fa-sign-out-alt fa-fw"></i> Logout
            </a>
            <form id="logout-form"
                  action="<?= route('web.auth.logout') ?>"
                  method="POST"
                  style="display:none;">
                <input type="hidden" name="_csrf" value="<?= e($_csrf ?? '') ?>">
            </form>
        </div>
    </div>
</header>
<!-- End Topbar -->
