<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$fromDate = clean($_GET['from'] ?? date('Y-m-01'));
$toDate = clean($_GET['to'] ?? date('Y-m-d'));

$pdo = Database::getConnection();
$stmt = $pdo->prepare(
    "SELECT p.payment_code, p.amount_paid, p.payment_date, p.payment_method, l.loan_code,
            uc.full_name AS customer_name, ue.full_name AS officer_name
     FROM payments p
     INNER JOIN loans l ON l.loan_id = p.loan_id
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     INNER JOIN users ue ON ue.user_id = p.received_by
     WHERE p.payment_date BETWEEN :from AND :to
     ORDER BY p.payment_date DESC"
);
$stmt->execute(['from' => $fromDate, 'to' => $toDate]);
$rows = $stmt->fetchAll();

$totalCollected = array_sum(array_column($rows, 'amount_paid'));

$byOfficer = [];
foreach ($rows as $r) {
    $byOfficer[$r['officer_name']] = ($byOfficer[$r['officer_name']] ?? 0) + (float) $r['amount_paid'];
}

$pageTitle = 'Collection Report';
$activeMenu = 'reports';
$breadcrumb = ['Reports' => '/admin/reports/index.php', 'Collections' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mfs-card p-3 mb-4 d-print-none">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">From</label>
            <input type="date" name="from" class="form-control" value="<?= e($fromDate) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">To</label>
            <input type="date" name="to" class="form-control" value="<?= e($toDate) ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-navy w-100">Apply</button>
        </div>
        <div class="col-md-2 ms-auto">
            <button type="button" class="btn btn-outline-secondary w-100" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="mfs-stat-tile accent-success"><div class="stat-label">Total Collected</div><div class="stat-value" style="font-size:1.3rem;"><?= money($totalCollected) ?></div></div>
    </div>
    <div class="col-md-6">
        <div class="mfs-card p-3">
            <div class="stat-label mb-2">By Officer</div>
            <?php foreach ($byOfficer as $name => $amt): ?>
                <div class="d-flex justify-content-between small"><span><?= e($name) ?></span><span><?= money($amt) ?></span></div>
            <?php endforeach; ?>
            <?php if (empty($byOfficer)): ?><p class="text-muted small mb-0">No collections in this period.</p><?php endif; ?>
        </div>
    </div>
</div>

<div class="mfs-card p-3">
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead><tr><th>Code</th><th>Loan</th><th>Customer</th><th>Amount</th><th>Method</th><th>Officer</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['payment_code']) ?></td>
                    <td><?= e($row['loan_code']) ?></td>
                    <td><?= e($row['customer_name']) ?></td>
                    <td><?= money($row['amount_paid']) ?></td>
                    <td><?= e(ucwords(str_replace('_', ' ', $row['payment_method']))) ?></td>
                    <td><?= e($row['officer_name']) ?></td>
                    <td><?= display_date($row['payment_date']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No payments in this period.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
