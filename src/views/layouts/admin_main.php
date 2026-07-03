<?php

/**
 * Main admin layout wrapper.
 *
 * Expected variables (all injected via render()):
 *   $content      string                   pre-rendered inner page HTML
 *   $pageTitle    string
 *   $theme        array{primary, secondary, light, dark}
 *   $setting      array<string,mixed>|null
 *   $themeName    string
 *   $themes       array
 *   $_csrf        string
 *   $currentUser  object|array|null
 *   $flash        array{success?: string, error?: string}
 *
 * Usage pattern in controllers:
 *   ob_start();
 *   extract($innerData, EXTR_SKIP);
 *   include APP_ROOT . '/src/views/some/page.php';
 *   $content = ob_get_clean();
 *   render(APP_ROOT . '/src/views/layouts/admin_main.php', array_merge($layoutData, ['content' => $content]));
 */

declare(strict_types=1);

$_layoutDir = __DIR__;
include $_layoutDir . '/admin_head.php';
?>
<body>
<div class="flex min-h-screen">

    <?php include $_layoutDir . '/admin_sidebar.php'; ?>

    <!-- Main content area (offset by sidebar width on md+) -->
    <div class="flex-1 md:ml-64 flex flex-col min-h-screen">

        <?php include $_layoutDir . '/admin_topbar.php'; ?>

        <main class="flex-1 px-4 md:px-8 pb-8">
            <?= $content ?? '' ?>
        </main>

    </div>
</div>
<?php include $_layoutDir . '/admin_foot.php'; ?>
