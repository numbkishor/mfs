<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_CASHIER);
refresh_overdue_statuses();

$status = clean($_GET['status'] ?? '');
$search = clean($_GET['q'] ?? '');
$pdo = Database::getConnection();

// Repayments can only be collected on loans that have been disbursed.
$where = 'd.disbursement_id IS NOT NULL';
$params = [];

if (in_array($status, ['pending', 'partial', 'overdue', 'paid'], true)) {
    $where .= ' AND rs.status = :status';
    $params['status'] = $status;
} else {
    $where .= " AND rs.status IN ('pending','partial','overdue')";
}

if ($search !== '') {
    // One named placeholder per LIKE: native prepares bind each exactly once.
    $where .= ' AND (l.loan_code LIKE :q1 OR uc.full_name LIKE :q2 OR c.customer_code LIKE :q3)';
    $params['q1'] = '%' . $search . '%';
    $params['q2'] = $params['q1'];
    $params['q3'] = $params['q1'];
}

$stmt = $pdo->prepare(
    "SELECT rs.schedule_id, rs.installment_no, rs.due_date, rs.total_due, rs.amount_paid, rs.status,
            l.loan_id, l.loan_code, uc.full_name AS customer_name
     FROM repayment_schedules rs
     INNER JOIN loans l ON l.loan_id = rs.loan_id
     INNER JOIN disbursements d ON d.loan_id = l.loan_id
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     WHERE {$where}
     ORDER BY rs.due_date ASC
     LIMIT 100"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Repayments';
$activeMenu = 'repayments';
$breadcrumb = ['Repayments' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<form method="get" class="mb-3 d-flex gap-2 flex-wrap">
    <select name="status" class="form-select w-auto" onchange="this.form.submit()">
        <option value="">Due &amp; overdue</option>
        <option value="overdue" <?= $status === 'overdue' ? 'selected' : '' ?>>Overdue only</option>
        <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
    </select>
    <input type="text" name="q" class="form-control w-auto" placeholder="Loan code / customer" value="<?= e($search) ?>">
    <button type="submit" class="btn btn-outline-secondary">Search</button>
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
                    <td class="text-end">
                        <?php if ($row['status'] !== 'paid'): ?>
                            <a class="btn btn-sm btn-brass" href="<?= e(BASE_URL) ?>/cashier/repayments/record_payment.php?loan_id=<?= (int) $row['loan_id'] ?>&schedule_id=<?= (int) $row['schedule_id'] ?>">Collect Payment</a>
                        <?php else: ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(BASE_URL) ?>/cashier/loans/view.php?id=<?= (int) $row['loan_id'] ?>">View Loan</a>
                        <?php endif; ?>
                    </td>
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
