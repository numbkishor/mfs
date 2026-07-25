<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_CASHIER);

$pdo = Database::getConnection();

$awaiting = $pdo->query(
    "SELECT l.loan_id, l.loan_code, l.principal_amount, l.start_date, l.duration_months,
            uc.full_name AS customer_name, c.customer_code, lt.type_name
     FROM loans l
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     INNER JOIN loan_types lt ON lt.loan_type_id = l.loan_type_id
     LEFT JOIN disbursements d ON d.loan_id = l.loan_id
     WHERE l.status = 'active' AND d.disbursement_id IS NULL
     ORDER BY l.created_at ASC"
)->fetchAll();

$recent = $pdo->query(
    "SELECT d.*, l.loan_code, uc.full_name AS customer_name, ub.full_name AS cashier_name
     FROM disbursements d
     INNER JOIN loans l ON l.loan_id = d.loan_id
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     INNER JOIN users ub ON ub.user_id = d.disbursed_by
     ORDER BY d.created_at DESC
     LIMIT 20"
)->fetchAll();

$pageTitle = 'Disbursements';
$activeMenu = 'disbursements';
$breadcrumb = ['Disbursements' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mfs-card p-3 mb-4">
    <h2 class="h6 mb-3">Approved loans awaiting disbursement</h2>
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead><tr><th>Loan</th><th>Customer</th><th>Type</th><th>Principal</th><th>Start</th><th>Duration</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            <?php foreach ($awaiting as $row): ?>
                <tr>
                    <td><?= e($row['loan_code']) ?></td>
                    <td><?= e($row['customer_name']) ?> <span class="text-muted">(<?= e($row['customer_code']) ?>)</span></td>
                    <td><?= e($row['type_name']) ?></td>
                    <td><?= money($row['principal_amount']) ?></td>
                    <td><?= display_date($row['start_date']) ?></td>
                    <td><?= (int) $row['duration_months'] ?> months</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(BASE_URL) ?>/cashier/loans/view.php?id=<?= (int) $row['loan_id'] ?>">View</a>
                        <a class="btn btn-sm btn-brass" href="<?= e(BASE_URL) ?>/cashier/disbursements/disburse.php?id=<?= (int) $row['loan_id'] ?>">Disburse</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($awaiting)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No approved loans are waiting for disbursement.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mfs-card p-3">
    <h2 class="h6 mb-3">Recent disbursements</h2>
    <div class="table-responsive">
        <table class="table table-sm mfs-table align-middle">
            <thead><tr><th>Loan</th><th>Customer</th><th>Amount</th><th>Date</th><th>Method</th><th>Reference</th><th>Disbursed By</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $row): ?>
                <tr>
                    <td><?= e($row['loan_code']) ?></td>
                    <td><?= e($row['customer_name']) ?></td>
                    <td><?= money($row['disbursed_amount']) ?></td>
                    <td><?= display_date($row['disbursement_date']) ?></td>
                    <td><?= e(ucwords(str_replace('_', ' ', $row['method']))) ?></td>
                    <td><?= e($row['reference_no'] ?? '—') ?></td>
                    <td><?= e($row['cashier_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No disbursements recorded yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
