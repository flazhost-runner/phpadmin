<?php

/**
 * Full-width (auth) layout — no sidebar, no topbar.
 *
 * Expected variables (all injected via render()):
 *   $content      string                   pre-rendered inner page HTML
 *   $pageTitle    string
 *   $theme        array{primary, secondary, light, dark}
 *   $setting      array<string,mixed>|null
 *   $_csrf        string
 *   $flash        array{success?: string, error?: string}
 *   $currentUser  null                     (always null on auth pages)
 *
 * Usage pattern in controllers:
 *   ob_start();
 *   extract($innerData, EXTR_SKIP);
 *   include APP_ROOT . '/src/views/auth/login.php';
 *   $content = ob_get_clean();
 *   render(APP_ROOT . '/src/views/layouts/full_width.php', array_merge($layoutData, ['content' => $content]));
 */

declare(strict_types=1);

$_layoutDir = __DIR__;
include $_layoutDir . '/admin_head.php';
?>
<body>
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <?= $content ?? '' ?>
</div>
<?php include $_layoutDir . '/admin_foot.php'; ?>
