<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_OFFICER);

$status = clean($_GET['status'] ?? '');
$user = current_user();

$countRows = fetch_applications_list($status, $user['user_id'], 1000000, 0);
$pg = paginate($countRows['total'], 10);
$result = fetch_applications_list($status, $user['user_id'], $pg['limit'], $pg['offset']);

$pageTitle = 'My Applications';
$activeMenu = 'applications';
$breadcrumb = ['Applications' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between mb-3">
    <form method="get" class="d-flex gap-2">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <?php foreach (['pending', 'approved', 'rejected', 'returned'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <a href="<?= e(BASE_URL) ?>/officer/applications/create.php" class="btn btn-brass"><i class="bi bi-file-earmark-plus"></i> New Application</a>
</div>

<div class="mfs-card p-3">
    <?php
    $rows = $result['rows'];
    $basePath = '/officer/applications';
    $showOfficer = false;
    include __DIR__ . '/../../includes/partials/applications_table.php';
    render_pagination($pg['page'], $pg['total_pages']);
    ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
