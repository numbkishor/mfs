<?php

declare(strict_types=1);

/**
 * Shared document head for every page (app shell + auth screens).
 *
 * Expects the including page to define:
 *   $pageTitle (string) — used for the browser tab title.
 *
 * Vendor CSS/JS is served from /assets/vendor rather than a CDN, so the
 * app renders correctly on a XAMPP box with no internet connection.
 */

$pageTitle = $pageTitle ?? APP_NAME;
$assetVersion = APP_VERSION;
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0f1114" media="(prefers-color-scheme: dark)">
<meta name="description" content="<?= e(APP_NAME) ?>">
<meta name="referrer" content="same-origin">
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>

<script>
/* Applies the saved theme before first paint so the page never flashes. */
(function () {
    var theme = 'light';
    try {
        var saved = localStorage.getItem('mfs-theme');
        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        theme = saved || (prefersDark ? 'dark' : 'light');
    } catch (e) { /* storage blocked — fall back to light */ }

    var root = document.documentElement;
    root.setAttribute('data-bs-theme', theme);
    root.setAttribute('data-mfs-theme', theme);
})();
</script>

<link href="<?= e(BASE_URL) ?>/assets/vendor/bootstrap/bootstrap.min.css?v=<?= e($assetVersion) ?>" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css?v=<?= e($assetVersion) ?>" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>/assets/css/style.css?v=<?= e($assetVersion) ?>" rel="stylesheet">
