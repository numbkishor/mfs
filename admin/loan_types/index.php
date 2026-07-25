<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$pdo = Database::getConnection();
$loanTypes = $pdo->query('SELECT * FROM loan_types ORDER BY type_name ASC')->fetchAll();

$pageTitle = 'Loan Types';
$activeMenu = 'loan_types';
$breadcrumb = ['Loan Types' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= e(BASE_URL) ?>/admin/loan_types/create.php" class="btn btn-brass"><i class="bi bi-plus-lg"></i> New Loan Type</a>
</div>

<div class="mfs-card p-3">
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead>
            <tr>
                <th>Name</th><th>Interest Rate</th><th>Amount Range</th><th>Duration</th><th>Processing Fee</th><th>Status</th><th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($loanTypes as $lt): ?>
                <tr>
                    <td><?= e($lt['type_name']) ?><div class="text-muted small"><?= e($lt['description'] ?? '') ?></div></td>
                    <td><?= number_format((float) $lt['interest_rate'], 2) ?>% p.a.</td>
                    <td><?= money($lt['min_amount']) ?> &ndash; <?= money($lt['max_amount']) ?></td>
                    <td><?= (int) $lt['min_duration_months'] ?> &ndash; <?= (int) $lt['max_duration_months'] ?> mo</td>
                    <td><?= number_format((float) $lt['processing_fee_pct'], 2) ?>%</td>
                    <td><?= status_badge($lt['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= e(BASE_URL) ?>/admin/loan_types/edit.php?id=<?= (int) $lt['loan_type_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <a href="<?= e(BASE_URL) ?>/admin/loan_types/delete.php?id=<?= (int) $lt['loan_type_id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Deactivate this loan type?"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($loanTypes)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No loan types configured yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
