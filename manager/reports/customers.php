<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_MANAGER);

$pdo = Database::getConnection();

$rows = $pdo->query(
    "SELECT c.customer_id, c.customer_code, u.full_name, c.city, c.status,
            COUNT(DISTINCT l.loan_id) AS loan_count,
            COALESCE(SUM(l.principal_amount), 0) AS total_borrowed
     FROM customers c
     INNER JOIN users u ON u.user_id = c.user_id
     LEFT JOIN loans l ON l.customer_id = c.customer_id
     GROUP BY c.customer_id, c.customer_code, u.full_name, c.city, c.status
     ORDER BY total_borrowed DESC"
)->fetchAll();

foreach ($rows as &$row) {
    $row['outstanding'] = 0.0;
    $loanStmt = $pdo->prepare("SELECT loan_id FROM loans WHERE customer_id = :id AND status = 'active'");
    $loanStmt->execute(['id' => $row['customer_id']]);
    foreach ($loanStmt->fetchAll() as $l) {
        $row['outstanding'] += get_loan_outstanding_balance((int) $l['loan_id']);
    }
}
unset($row);

$pageTitle = 'Customer Report';
$activeMenu = 'reports';
$breadcrumb = ['Reports' => '/manager/reports/index.php', 'Customers' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-end mb-3 d-print-none">
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="mfs-card p-3">
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead><tr><th>Code</th><th>Name</th><th>City</th><th>Status</th><th>Loans</th><th>Total Borrowed</th><th>Outstanding</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['customer_code']) ?></td>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['city']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td><?= (int) $row['loan_count'] ?></td>
                    <td><?= money($row['total_borrowed']) ?></td>
                    <td><?= money($row['outstanding']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No customers on record.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
