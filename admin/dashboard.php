<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);
refresh_overdue_statuses();

$pdo = Database::getConnection();

$stats = [
    'customers'   => (int) $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'active'")->fetchColumn(),
    'pending_apps'=> (int) $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status = 'pending'")->fetchColumn(),
    'active_loans'=> (int) $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn(),
    'overdue'     => (int) $pdo->query("SELECT COUNT(*) FROM repayment_schedules WHERE status = 'overdue'")->fetchColumn(),
];

$cashFlow = get_cash_flow_summary();
$monthly = get_monthly_cash_flow(6);

$recentApps = $pdo->query(
    "SELECT a.application_code, a.requested_amount, a.status, a.created_at, u.full_name AS customer_name
     FROM loan_applications a
     INNER JOIN customers c ON c.customer_id = a.customer_id
     INNER JOIN users u ON u.user_id = c.user_id
     ORDER BY a.created_at DESC LIMIT 6"
)->fetchAll();

$recentPayments = $pdo->query(
    "SELECT p.payment_code, p.amount_paid, p.payment_date, l.loan_code, u.full_name AS customer_name
     FROM payments p
     INNER JOIN loans l ON l.loan_id = p.loan_id
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users u ON u.user_id = c.user_id
     ORDER BY p.created_at DESC LIMIT 6"
)->fetchAll();

$pageTitle = 'Administrator Dashboard';
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

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="mfs-stat-tile accent-success h-100">
            <div class="stat-label">Available Cash</div>
            <div class="stat-value"><?= money($cashFlow['available_cash']) ?></div>
            <div class="text-muted small mt-1">Total repaid minus total disbursed</div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mfs-stat-tile h-100">
            <div class="stat-label">Total Disbursed</div>
            <div class="stat-value"><?= money($cashFlow['total_disbursed']) ?></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mfs-stat-tile accent-brass h-100">
            <div class="stat-label">Interest Collected</div>
            <div class="stat-value"><?= money($cashFlow['interest_collected']) ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="mfs-card p-3">
            <h2 class="h6 mb-3">Disbursement vs Repayment — last 6 months</h2>
            <canvas id="cashFlowChart" height="220"></canvas>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="mfs-card p-3 h-100">
            <h2 class="h6 mb-3">Quick Links</h2>
            <div class="d-grid gap-2">
                <a href="<?= e(BASE_URL) ?>/admin/applications/index.php?status=pending" class="btn btn-outline-secondary text-start"><i class="bi bi-file-earmark-text me-2"></i>Review pending applications</a>
                <a href="<?= e(BASE_URL) ?>/admin/loan_types/create.php" class="btn btn-outline-secondary text-start"><i class="bi bi-tags me-2"></i>Add a new loan type</a>
                <a href="<?= e(BASE_URL) ?>/admin/users/create.php" class="btn btn-outline-secondary text-start"><i class="bi bi-person-badge me-2"></i>Create employee account</a>
                <a href="<?= e(BASE_URL) ?>/admin/reports/index.php" class="btn btn-outline-secondary text-start"><i class="bi bi-bar-chart-line me-2"></i>Open reports centre</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="mfs-card p-3">
            <h2 class="h6 mb-3">Recent Applications</h2>
            <div class="table-responsive">
                <table class="table table-sm mfs-table align-middle">
                    <thead><tr><th>Code</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentApps as $row): ?>
                        <tr>
                            <td><?= e($row['application_code']) ?></td>
                            <td><?= e($row['customer_name']) ?></td>
                            <td><?= money($row['requested_amount']) ?></td>
                            <td><?= status_badge($row['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentApps)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No applications yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mfs-card p-3">
            <h2 class="h6 mb-3">Recent Payments</h2>
            <div class="table-responsive">
                <table class="table table-sm mfs-table align-middle">
                    <thead><tr><th>Code</th><th>Customer</th><th>Loan</th><th>Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentPayments as $row): ?>
                        <tr>
                            <td><?= e($row['payment_code']) ?></td>
                            <td><?= e($row['customer_name']) ?></td>
                            <td><?= e($row['loan_code']) ?></td>
                            <td><?= money($row['amount_paid']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentPayments)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No payments yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('cashFlowChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthly, 'month')) ?>,
        datasets: [
            { label: 'Disbursed', data: <?= json_encode(array_column($monthly, 'disbursed')) ?>, backgroundColor: '#16384f' },
            { label: 'Repaid', data: <?= json_encode(array_column($monthly, 'repaid')) ?>, backgroundColor: '#c98a3e' }
        ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
