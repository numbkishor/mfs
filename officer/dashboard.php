<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_OFFICER);
refresh_overdue_statuses();

$pdo = Database::getConnection();
$user = current_user();

$myCustomersStmt = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE registered_by = ?');
$myCustomersStmt->execute([$user['user_id']]);

$stats = [
    'my_customers' => (int) $myCustomersStmt->fetchColumn(),
    'my_pending_apps' => (int) $pdo->query('SELECT COUNT(*) FROM loan_applications WHERE submitted_by = ' . (int) $user['user_id'] . " AND status = 'pending'")->fetchColumn(),
    'active_loans'    => (int) $pdo->query('SELECT COUNT(*) FROM loans l INNER JOIN loan_applications a ON a.application_id = l.application_id WHERE a.submitted_by = ' . (int) $user['user_id'] . " AND l.status = 'active'")->fetchColumn(),
    'overdue'         => (int) $pdo->query('SELECT COUNT(*) FROM repayment_schedules rs INNER JOIN loans l ON l.loan_id = rs.loan_id INNER JOIN loan_applications a ON a.application_id = l.application_id WHERE a.submitted_by = ' . (int) $user['user_id'] . " AND rs.status = 'overdue'")->fetchColumn(),
];

$approvedAwaitingLoan = $pdo->prepare(
    "SELECT a.application_id, a.application_code, a.requested_amount, u.full_name AS customer_name
     FROM loan_applications a
     INNER JOIN customers c ON c.customer_id = a.customer_id
     INNER JOIN users u ON u.user_id = c.user_id
     LEFT JOIN loans l ON l.application_id = a.application_id
     WHERE a.status = 'approved' AND l.loan_id IS NULL AND a.submitted_by = ?
     ORDER BY a.reviewed_at ASC"
);
$approvedAwaitingLoan->execute([$user['user_id']]);
$approvedAwaitingLoan = $approvedAwaitingLoan->fetchAll();

$dueSoon = $pdo->prepare(
    "SELECT rs.schedule_id, rs.due_date, rs.total_due, rs.amount_paid, rs.status, l.loan_code, uc.full_name AS customer_name
     FROM repayment_schedules rs
     INNER JOIN loans l ON l.loan_id = rs.loan_id
     INNER JOIN loan_applications a ON a.application_id = l.application_id
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     WHERE a.submitted_by = ? AND rs.status IN ('pending','overdue','partial')
     ORDER BY rs.due_date ASC LIMIT 8"
);
$dueSoon->execute([$user['user_id']]);
$dueSoon = $dueSoon->fetchAll();

$pageTitle = 'Loan Officer Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile">
            <div class="stat-label">My Customers</div>
            <div class="stat-value"><?= number_format($stats['my_customers']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile accent-brass">
            <div class="stat-label">My Pending Applications</div>
            <div class="stat-value"><?= number_format($stats['my_pending_apps']) ?></div>
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

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="mfs-card p-3">
            <h2 class="h6 mb-3">Quick Actions</h2>
            <div class="d-grid gap-2">
                <a href="<?= e(BASE_URL) ?>/officer/customers/create.php" class="btn btn-brass"><i class="bi bi-person-plus me-2"></i>Register new customer</a>
                <a href="<?= e(BASE_URL) ?>/officer/applications/create.php" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-plus me-2"></i>Submit loan application</a>
                <a href="<?= e(BASE_URL) ?>/officer/repayments/index.php" class="btn btn-outline-secondary"><i class="bi bi-cash me-2"></i>Track repayments</a>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="mfs-card p-3">
            <h2 class="h6 mb-3">Approved — awaiting loan generation</h2>
            <div class="table-responsive">
                <table class="table table-sm mfs-table align-middle">
                    <thead><tr><th>Code</th><th>Customer</th><th>Amount</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($approvedAwaitingLoan as $row): ?>
                        <tr>
                            <td><?= e($row['application_code']) ?></td>
                            <td><?= e($row['customer_name']) ?></td>
                            <td><?= money($row['requested_amount']) ?></td>
                            <td><a class="btn btn-sm btn-navy" href="<?= e(BASE_URL) ?>/officer/loans/generate.php?application_id=<?= (int) $row['application_id'] ?>">Generate Loan</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($approvedAwaitingLoan)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Nothing waiting right now.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mfs-card p-3">
    <h2 class="h6 mb-3">Upcoming &amp; overdue installments</h2>
    <div class="table-responsive">
        <table class="table table-sm mfs-table align-middle">
            <thead><tr><th>Loan</th><th>Customer</th><th>Due Date</th><th>Amount Due</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($dueSoon as $row): ?>
                <tr>
                    <td><?= e($row['loan_code']) ?></td>
                    <td><?= e($row['customer_name']) ?></td>
                    <td><?= display_date($row['due_date']) ?></td>
                    <td><?= money($row['total_due'] - $row['amount_paid']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($dueSoon)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No upcoming installments.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
