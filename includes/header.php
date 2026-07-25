<?php

declare(strict_types=1);

/**
 * Expects the including page to have already defined:
 *   $pageTitle   (string) browser tab / H1 title
 *   $activeMenu  (string) key matching a sidebar link, for highlighting
 *   $breadcrumb  (array)  ['Label' => url|null, ...] optional
 *
 * And to have already required includes/auth.php + called require_login()/require_role().
 */

$user = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
$activeMenu = $activeMenu ?? '';
$breadcrumb = $breadcrumb ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,500;8..60,600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(BASE_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="mfs-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="mfs-main">
        <header class="mfs-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary d-lg-none" type="button" aria-label="Toggle menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="h5 mb-0"><?= e($pageTitle) ?></h1>
                    <?php if (!empty($breadcrumb)): ?>
                        <nav class="mfs-breadcrumb">
                            <a href="<?= e(BASE_URL) ?><?= e(role_home_path($user['role_id'])) ?>">Dashboard</a>
                            <?php foreach ($breadcrumb as $label => $url): ?>
                                &nbsp;/&nbsp;
                                <?php if ($url): ?>
                                    <a href="<?= e(BASE_URL . $url) ?>"><?= e($label) ?></a>
                                <?php else: ?>
                                    <span><?= e($label) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="role-badge"><?= e($user['role_name']) ?></span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= e($user['full_name']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item disabled" href="#"><?= e($user['username']) ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= e(BASE_URL) ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="mfs-content">
            <?php render_flash(); ?>
