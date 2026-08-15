<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$status = clean($_GET['status'] ?? '');

$countRows = fetch_loans_list($status, null, 0, 0);
$pg = paginate($countRows['total'], 10);
$result = fetch_loans_list($status, null, $pg['limit'], $pg['offset']);

$pageTitle = 'Loans';
$activeMenu = 'loans';
$breadcrumb = ['Loans' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<form method="get" class="mb-3">
    <select name="status" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <?php foreach (['active', 'closed', 'defaulted', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="mfs-card p-3">
    <?php
    $rows = $result['rows'];
    $basePath = '/admin/loans';
    include __DIR__ . '/../../includes/partials/loans_table.php';
    render_pagination($pg['page'], $pg['total_pages']);
    ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
