<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$pdo = Database::getConnection();

$rows = $pdo->query(
    "SELECT l.loan_id, l.loan_code, l.principal_amount, l.interest_rate, l.status, l.start_date,
            uc.full_name AS customer_name, lt.type_name
     FROM loans l
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     INNER JOIN loan_types lt ON lt.loan_type_id = l.loan_type_id
     ORDER BY l.created_at DESC"
)->fetchAll();

$totalPrincipal = 0.0;
$totalOutstanding = 0.0;
foreach ($rows as &$row) {
    $row['outstanding'] = get_loan_outstanding_balance((int) $row['loan_id']);
    $totalPrincipal += (float) $row['principal_amount'];
    $totalOutstanding += $row['outstanding'];
}
unset($row);

$pageTitle = 'Loan Report';
$activeMenu = 'reports';
$breadcrumb = ['Reports' => '/admin/reports/index.php', 'Loans' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-end mb-3 d-print-none">
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="mfs-stat-tile"><div class="stat-label">Total Principal Issued</div><div class="stat-value" style="font-size:1.3rem;"><?= money($totalPrincipal) ?></div></div>
    </div>
    <div class="col-md-6">
        <div class="mfs-stat-tile accent-danger"><div class="stat-label">Total Outstanding</div><div class="stat-value" style="font-size:1.3rem;"><?= money($totalOutstanding) ?></div></div>
    </div>
</div>

<div class="mfs-card p-3">
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead><tr><th>Loan</th><th>Customer</th><th>Type</th><th>Principal</th><th>Rate</th><th>Outstanding</th><th>Status</th><th>Start</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['loan_code']) ?></td>
                    <td><?= e($row['customer_name']) ?></td>
                    <td><?= e($row['type_name']) ?></td>
                    <td><?= money($row['principal_amount']) ?></td>
                    <td><?= number_format((float) $row['interest_rate'], 2) ?>%</td>
                    <td><?= money($row['outstanding']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td><?= display_date($row['start_date']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No loans on record.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
