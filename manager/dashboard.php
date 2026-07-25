<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_MANAGER);
refresh_overdue_statuses();

$pdo = Database::getConnection();

$stats = [
    'customers'    => (int) $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'active'")->fetchColumn(),
    'pending_apps' => (int) $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status = 'pending'")->fetchColumn(),
    'active_loans' => (int) $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn(),
    'overdue'      => (int) $pdo->query("SELECT COUNT(*) FROM repayment_schedules WHERE status = 'overdue'")->fetchColumn(),
];

$cashFlow = get_cash_flow_summary();

$pendingApps = $pdo->query(
    "SELECT a.application_id, a.application_code, a.requested_amount, a.requested_duration_months, a.created_at,
            u.full_name AS customer_name, lt.type_name
     FROM loan_applications a
     INNER JOIN customers c ON c.customer_id = a.customer_id
     INNER JOIN users u ON u.user_id = c.user_id
     INNER JOIN loan_types lt ON lt.loan_type_id = a.loan_type_id
     WHERE a.status = 'pending'
     ORDER BY a.created_at ASC LIMIT 8"
)->fetchAll();

$pageTitle = 'Manager Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile">
            <div class="stat-label">Active Customers</div>
            <div class="stat-value"><?= number_format($stats['customers']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile accent-brass">
            <div class="stat-label">Pending Applications</div>
            <div class="stat-value"><?= number_format($stats['pending_apps']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile accent-success">
            <div class="stat-label">Active Loans</div>
            <div class="stat-value"><?= number_format($stats['active_loans']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile accent-danger">
            <div class="stat-label">Overdue Installments</div>
            <div class="stat-value"><?= number_format($stats['overdue']) ?></div>
        </div>
    </div>
</div>

<div class="mfs-card p-3 mb-4">
    <div class="stat-label">Available Cash</div>
    <div class="stat-value"><?= money($cashFlow['available_cash']) ?></div>
</div>

<div class="mfs-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0">Applications awaiting your decision</h2>
        <a href="<?= e(BASE_URL) ?>/manager/applications/index.php?status=pending" class="btn btn-sm btn-brass">Review all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mfs-table align-middle">
            <thead><tr><th>Code</th><th>Customer</th><th>Loan Type</th><th>Amount</th><th>Duration</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($pendingApps as $row): ?>
                <tr>
                    <td><?= e($row['application_code']) ?></td>
                    <td><?= e($row['customer_name']) ?></td>
                    <td><?= e($row['type_name']) ?></td>
                    <td><?= money($row['requested_amount']) ?></td>
                    <td><?= (int) $row['requested_duration_months'] ?> mo</td>
                    <td><?= display_date($row['created_at']) ?></td>
                    <td><a href="<?= e(BASE_URL) ?>/manager/applications/review.php?id=<?= (int) $row['application_id'] ?>" class="btn btn-sm btn-outline-secondary">Review</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pendingApps)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No pending applications.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
