<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);
refresh_overdue_statuses();

$pdo = Database::getConnection();
$rows = $pdo->query(
    "SELECT rs.installment_no, rs.due_date, rs.total_due, rs.amount_paid, l.loan_code,
            uc.full_name AS customer_name, uc.phone,
            DATEDIFF(CURDATE(), rs.due_date) AS days_overdue
     FROM repayment_schedules rs
     INNER JOIN loans l ON l.loan_id = rs.loan_id
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     WHERE rs.status = 'overdue'
     ORDER BY days_overdue DESC"
)->fetchAll();

$totalOverdue = 0.0;
foreach ($rows as $r) {
    $totalOverdue += (float) $r['total_due'] - (float) $r['amount_paid'];
}

$pageTitle = 'Overdue Report';
$activeMenu = 'reports';
$breadcrumb = ['Reports' => '/admin/reports/index.php', 'Overdue' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between mb-3 d-print-none">
    <div class="mfs-stat-tile accent-danger" style="min-width:260px;">
        <div class="stat-label">Total Overdue Amount</div>
        <div class="stat-value" style="font-size:1.3rem;"><?= money($totalOverdue) ?></div>
    </div>
    <button type="button" class="btn btn-outline-secondary align-self-start" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="mfs-card p-3">
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead><tr><th>Loan</th><th>Customer</th><th>Phone</th><th>Installment</th><th>Due Date</th><th>Days Overdue</th><th>Amount Due</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['loan_code']) ?></td>
                    <td><?= e($row['customer_name']) ?></td>
                    <td><?= e($row['phone'] ?? '—') ?></td>
                    <td>#<?= (int) $row['installment_no'] ?></td>
                    <td><?= display_date($row['due_date']) ?></td>
                    <td><span class="badge bg-danger"><?= (int) $row['days_overdue'] ?> days</span></td>
                    <td><?= money($row['total_due'] - $row['amount_paid']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No overdue installments. 🎉</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
