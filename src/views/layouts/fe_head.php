<?php

/**
 * Front-end head layout partial.
 *
 * Expected variables (injected via render()):
 *   $setting    array<string,mixed>   app settings (name, description, theme, ...)
 *   $pageTitle  string
 */

declare(strict_types=1);

$_siteName = e((string)($setting['name'] ?? 'PHPAdmin'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e((string)($setting['description'] ?? '')) ?>">
  <title><?= e($pageTitle ?? $_siteName) ?></title>
  <link rel="icon" type="image/png" href="/media/setting/favicon.png">

  <!-- Tailwind v4 browser CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Google Fonts — Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Font Awesome (local) -->
  <link rel="stylesheet" href="/be/default/vendor/fontawesome-free/css/all.min.css">

  <style>
    body { font-family: 'Inter', sans-serif; }
    html { scroll-behavior: smooth; }
  </style>
</head>
<body class="bg-white text-neutral-900 antialiased">
