<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_CASHIER);
refresh_overdue_statuses();

$pdo = Database::getConnection();
$user = current_user();

$stats = [
    'awaiting_disbursement' => (int) $pdo->query(
        "SELECT COUNT(*) FROM loans l LEFT JOIN disbursements d ON d.loan_id = l.loan_id
         WHERE l.status = 'active' AND d.disbursement_id IS NULL"
    )->fetchColumn(),
    'disbursed_today' => (float) $pdo->query(
        "SELECT COALESCE(SUM(disbursed_amount), 0) FROM disbursements WHERE disbursement_date = CURDATE()"
    )->fetchColumn(),
    'collected_today' => (float) $pdo->query(
        "SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE payment_date = CURDATE()"
    )->fetchColumn(),
    'overdue' => (int) $pdo->query(
        "SELECT COUNT(*) FROM repayment_schedules rs
         INNER JOIN loans l ON l.loan_id = rs.loan_id
         INNER JOIN disbursements d ON d.loan_id = l.loan_id
         WHERE rs.status = 'overdue'"
    )->fetchColumn(),
];

$awaiting = $pdo->query(
    "SELECT l.loan_id, l.loan_code, l.principal_amount, l.start_date, uc.full_name AS customer_name
     FROM loans l
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     LEFT JOIN disbursements d ON d.loan_id = l.loan_id
     WHERE l.status = 'active' AND d.disbursement_id IS NULL
     ORDER BY l.created_at ASC
     LIMIT 8"
)->fetchAll();

$dueSoon = $pdo->query(
    "SELECT rs.schedule_id, rs.installment_no, rs.due_date, rs.total_due, rs.amount_paid, rs.status,
            l.loan_id, l.loan_code, uc.full_name AS customer_name
     FROM repayment_schedules rs
     INNER JOIN loans l ON l.loan_id = rs.loan_id
     INNER JOIN disbursements d ON d.loan_id = l.loan_id
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     WHERE rs.status IN ('pending','partial','overdue')
     ORDER BY rs.due_date ASC
     LIMIT 8"
)->fetchAll();

$pageTitle = 'Cashier Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile accent-brass">
            <div class="stat-label">Awaiting Disbursement</div>
            <div class="stat-value"><?= number_format($stats['awaiting_disbursement']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile">
            <div class="stat-label">Disbursed Today</div>
            <div class="stat-value"><?= money($stats['disbursed_today']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile accent-success">
            <div class="stat-label">Collected Today</div>
            <div class="stat-value"><?= money($stats['collected_today']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile accent-danger">
            <div class="stat-label">Overdue Installments</div>
            <div class="stat-value"><?= number_format($stats['overdue']) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="mfs-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Approved loans awaiting disbursement</h2>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(BASE_URL) ?>/cashier/disbursements/index.php">All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mfs-table align-middle">
                    <thead><tr><th>Loan</th><th>Customer</th><th>Principal</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($awaiting as $row): ?>
                        <tr>
                            <td><?= e($row['loan_code']) ?></td>
                            <td><?= e($row['customer_name']) ?></td>
                            <td><?= money($row['principal_amount']) ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-brass" href="<?= e(BASE_URL) ?>/cashier/disbursements/disburse.php?id=<?= (int) $row['loan_id'] ?>">Disburse</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($awaiting)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Nothing waiting for disbursement.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mfs-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Due &amp; overdue installments</h2>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(BASE_URL) ?>/cashier/repayments/index.php">All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mfs-table align-middle">
                    <thead><tr><th>Loan</th><th>Customer</th><th>Due</th><th>Amount</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($dueSoon as $row): ?>
                        <tr>
                            <td><?= e($row['loan_code']) ?></td>
                            <td><?= e($row['customer_name']) ?></td>
                            <td><?= display_date($row['due_date']) ?></td>
                            <td><?= money($row['total_due'] - $row['amount_paid']) ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-navy" href="<?= e(BASE_URL) ?>/cashier/repayments/record_payment.php?loan_id=<?= (int) $row['loan_id'] ?>&schedule_id=<?= (int) $row['schedule_id'] ?>">Collect</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($dueSoon)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No installments due.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
