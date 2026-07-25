<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_MANAGER);
refresh_overdue_statuses();

$status = clean($_GET['status'] ?? '');
$pdo = Database::getConnection();

$where = "1=1";
$params = [];
if (in_array($status, ['pending', 'partial', 'overdue', 'paid'], true)) {
    $where = 'rs.status = :status';
    $params['status'] = $status;
} else {
    $where = "rs.status IN ('pending','partial','overdue')";
}

$stmt = $pdo->prepare(
    "SELECT rs.schedule_id, rs.installment_no, rs.due_date, rs.total_due, rs.amount_paid, rs.status,
            l.loan_id, l.loan_code, uc.full_name AS customer_name
     FROM repayment_schedules rs
     INNER JOIN loans l ON l.loan_id = rs.loan_id
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     WHERE {$where}
     ORDER BY rs.due_date ASC
     LIMIT 150"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Repayments';
$activeMenu = 'repayments';
$breadcrumb = ['Repayments' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<form method="get" class="mb-3">
    <select name="status" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
        <option value="">Due &amp; overdue</option>
        <option value="overdue" <?= $status === 'overdue' ? 'selected' : '' ?>>Overdue only</option>
        <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
    </select>
</form>

<div class="mfs-card p-3">
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead><tr><th>Loan</th><th>Customer</th><th>#</th><th>Due Date</th><th>Amount Due</th><th>Status</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['loan_code']) ?></td>
                    <td><?= e($row['customer_name']) ?></td>
                    <td><?= (int) $row['installment_no'] ?></td>
                    <td><?= display_date($row['due_date']) ?></td>
                    <td><?= money($row['total_due'] - $row['amount_paid']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= e(BASE_URL) ?>/manager/loans/view.php?id=<?= (int) $row['loan_id'] ?>">View Loan</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Nothing to show for this filter.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
