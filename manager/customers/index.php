<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_MANAGER);

$search = clean($_GET['search'] ?? '');
$status = clean($_GET['status'] ?? '');

$countRows = fetch_customers_list($search, $status, 0, 0);
$pg = paginate($countRows['total'], 10);
$result = fetch_customers_list($search, $status, $pg['limit'], $pg['offset']);

$pageTitle = 'Customers';
$activeMenu = 'customers';
$breadcrumb = ['Customers' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mfs-card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label small">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Name, code, NID, phone..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="blacklisted" <?= $status === 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-navy w-100" type="submit"><i class="bi bi-search"></i> Filter</button>
        </div>
    </form>
</div>

<div class="mfs-card p-3">
    <?php
    $rows = $result['rows'];
    $basePath = '/manager/customers';
    $canEdit = false;
    $canDelete = false;
    include __DIR__ . '/../../includes/partials/customers_table.php';
    render_pagination($pg['page'], $pg['total_pages']);
    ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
