<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login('customer');
require_role(ROLE_CUSTOMER);
refresh_overdue_statuses();

$user = current_user();
$customerId = $user['customer_id'];

if (!$customerId) {
    flash('danger', 'No customer profile is linked to this account. Please contact your loan officer.');
    require_once __DIR__ . '/../includes/header.php';
    echo '</main></div></div></body></html>';
    exit;
}

$pdo = Database::getConnection();

$loans = $pdo->prepare(
    "SELECT l.loan_id, l.loan_code, l.principal_amount, l.status, l.start_date, l.end_date, lt.type_name
     FROM loans l INNER JOIN loan_types lt ON lt.loan_type_id = l.loan_type_id
     WHERE l.customer_id = ? ORDER BY l.created_at DESC"
);
$loans->execute([$customerId]);
$loans = $loans->fetchAll();

$totalOutstanding = 0.0;
foreach ($loans as $loan) {
    if ($loan['status'] === 'active') {
        $totalOutstanding += get_loan_outstanding_balance((int) $loan['loan_id']);
    }
}

$nextDue = $pdo->prepare(
    "SELECT rs.due_date, rs.total_due, rs.amount_paid, l.loan_code
     FROM repayment_schedules rs INNER JOIN loans l ON l.loan_id = rs.loan_id
     WHERE l.customer_id = ? AND rs.status IN ('pending','overdue','partial')
     ORDER BY rs.due_date ASC LIMIT 1"
);
$nextDue->execute([$customerId]);
$nextDue = $nextDue->fetch();

$applications = $pdo->prepare(
    "SELECT application_code, requested_amount, status, created_at
     FROM loan_applications WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5"
);
$applications->execute([$customerId]);
$applications = $applications->fetchAll();

$pageTitle = 'My Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="mfs-stat-tile accent-danger">
            <div class="stat-label">Total Outstanding</div>
            <div class="stat-value"><?= money($totalOutstanding) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="mfs-stat-tile accent-brass">
            <div class="stat-label">Next Payment Due</div>
            <div class="stat-value" style="font-size:1.2rem;">
                <?= $nextDue ? display_date($nextDue['due_date']) : '—' ?>
            </div>
            <?php if ($nextDue): ?>
                <div class="text-muted small"><?= money($nextDue['total_due'] - $nextDue['amount_paid']) ?> on <?= e($nextDue['loan_code']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="mfs-stat-tile accent-success">
            <div class="stat-label">Loans on Record</div>
            <div class="stat-value"><?= count($loans) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="mfs-card p-3">
            <h2 class="h6 mb-3">My Loans</h2>
            <div class="table-responsive">
                <table class="table table-sm mfs-table align-middle">
                    <thead><tr><th>Loan</th><th>Type</th><th>Principal</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?= e($loan['loan_code']) ?></td>
                            <td><?= e($loan['type_name']) ?></td>
                            <td><?= money($loan['principal_amount']) ?></td>
                            <td><?= status_badge($loan['status']) ?></td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="<?= e(BASE_URL) ?>/customer/repayment_schedule.php?loan_id=<?= (int) $loan['loan_id'] ?>">Schedule</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($loans)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">You have no loans on record yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="mfs-card p-3">
            <h2 class="h6 mb-3">Recent Applications</h2>
            <div class="table-responsive">
                <table class="table table-sm mfs-table align-middle">
                    <thead><tr><th>Code</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= e($app['application_code']) ?></td>
                            <td><?= money($app['requested_amount']) ?></td>
                            <td><?= status_badge($app['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($applications)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">No applications on file.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
