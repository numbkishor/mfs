<?php

declare(strict_types=1);

/**
 * App shell: opens the document, sidebar, topbar and content region.
 * Every page closes it by requiring includes/footer.php.
 *
 * Expects the including page to have already defined:
 *   $pageTitle   (string) browser tab / H1 title
 *   $activeMenu  (string) key matching a sidebar link, for highlighting
 *   $breadcrumb  (array)  ['Label' => url|null, ...] optional
 *
 * And to have required includes/bootstrap.php then called
 * require_login() / require_role().
 */

$user = current_user();

// Reaching the shell without a session means an auth guard was skipped;
// fail closed rather than fataling on a null $user.
if ($user === null) {
    redirect('/auth/login_employee.php');
}

$pageTitle  = $pageTitle ?? APP_NAME;
$activeMenu = $activeMenu ?? '';
$breadcrumb = $breadcrumb ?? [];

$initials = '';
foreach (preg_split('/\s+/', trim((string) $user['full_name'])) ?: [] as $part) {
    if ($part !== '' && mb_strlen($initials) < 2) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}
$initials = $initials !== '' ? $initials : '?';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require __DIR__ . '/head.php'; ?>
</head>
<body>
<a class="mfs-skip-link" href="#mfs-content">Skip to content</a>

<div class="mfs-shell" id="mfsShell">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="mfs-backdrop" id="mfsBackdrop" aria-hidden="true"></div>

    <div class="mfs-main">
        <header class="mfs-topbar">
            <div class="d-flex align-items-center gap-2 gap-sm-3 min-w-0">
                <button id="sidebarToggle" class="mfs-icon-btn" type="button"
                        aria-label="Toggle navigation" aria-expanded="false" aria-controls="mfsSidebar"
                        title="Toggle navigation (\)">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <div class="min-w-0">
                    <h1><?= e($pageTitle) ?></h1>
                    <?php if (!empty($breadcrumb)): ?>
                        <nav class="mfs-breadcrumb" aria-label="Breadcrumb">
                            <a href="<?= e(BASE_URL . role_home_path((int) $user['role_id'])) ?>">Dashboard</a>
                            <?php foreach ($breadcrumb as $label => $url): ?>
                                <span class="sep" aria-hidden="true">/</span>
                                <?php if ($url): ?>
                                    <a href="<?= e(BASE_URL . $url) ?>"><?= e($label) ?></a>
                                <?php else: ?>
                                    <span aria-current="page"><?= e($label) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="role-badge"><?= e($user['role_name']) ?></span>

                <button type="button" class="mfs-icon-btn" data-theme-toggle aria-label="Switch colour theme">
                    <i class="bi" data-theme-icon aria-hidden="true"></i>
                </button>

                <div class="dropdown">
                    <button class="mfs-user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="mfs-avatar" aria-hidden="true"><?= e($initials) ?></span>
                        <span class="name"><?= e($user['full_name']) ?></span>
                        <i class="bi bi-chevron-down small" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-2 py-1">
                            <div class="small fw-semibold"><?= e($user['full_name']) ?></div>
                            <div class="small text-muted"><?= e($user['username']) ?></div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= e(BASE_URL) ?>/auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="mfs-content" id="mfs-content">
            <?php render_flash(); ?>
