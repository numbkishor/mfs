<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login('customer');
require_role(ROLE_CUSTOMER);
refresh_overdue_statuses();

$user = current_user();
$pdo = Database::getConnection();

$stmt = $pdo->prepare(
    "SELECT l.loan_id, l.loan_code, l.principal_amount, l.interest_rate, l.duration_months, l.status, l.start_date, l.end_date, lt.type_name
     FROM loans l INNER JOIN loan_types lt ON lt.loan_type_id = l.loan_type_id
     WHERE l.customer_id = ? ORDER BY l.created_at DESC"
);
$stmt->execute([$user['customer_id']]);
$loans = $stmt->fetchAll();

$pageTitle = 'My Loans';
$activeMenu = 'loans';
$breadcrumb = ['My Loans' => null];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mfs-card p-3">
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead><tr><th>Loan</th><th>Type</th><th>Principal</th><th>Rate</th><th>Duration</th><th>Outstanding</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($loans as $loan): ?>
                <tr>
                    <td><?= e($loan['loan_code']) ?></td>
                    <td><?= e($loan['type_name']) ?></td>
                    <td><?= money($loan['principal_amount']) ?></td>
                    <td><?= number_format((float) $loan['interest_rate'], 2) ?>%</td>
                    <td><?= (int) $loan['duration_months'] ?> mo</td>
                    <td><?= money(get_loan_outstanding_balance((int) $loan['loan_id'])) ?></td>
                    <td><?= status_badge($loan['status']) ?></td>
                    <td><a class="btn btn-sm btn-outline-secondary" href="<?= e(BASE_URL) ?>/customer/repayment_schedule.php?loan_id=<?= (int) $loan['loan_id'] ?>">Schedule</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($loans)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">You have no loans yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
