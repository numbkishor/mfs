<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login('customer');
require_role(ROLE_CUSTOMER);

$user = current_user();
$pdo = Database::getConnection();

$stmt = $pdo->prepare(
    "SELECT p.payment_code, p.amount_paid, p.payment_date, p.payment_method, p.reference_no, l.loan_code, rs.installment_no
     FROM payments p
     INNER JOIN loans l ON l.loan_id = p.loan_id
     INNER JOIN repayment_schedules rs ON rs.schedule_id = p.schedule_id
     WHERE l.customer_id = :id
     ORDER BY p.payment_date DESC"
);
$stmt->execute(['id' => $user['customer_id']]);
$payments = $stmt->fetchAll();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transaction_history.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Payment Code', 'Loan', 'Installment', 'Amount', 'Date', 'Method', 'Reference']);
    foreach ($payments as $p) {
        fputcsv($out, [$p['payment_code'], $p['loan_code'], $p['installment_no'], $p['amount_paid'], $p['payment_date'], $p['payment_method'], $p['reference_no']]);
    }
    fclose($out);
    exit;
}

$pageTitle = 'Payment History';
$activeMenu = 'payments';
$breadcrumb = ['Payment History' => null];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-end mb-3">
    <a href="?export=csv" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Download Transaction History (CSV)</a>
</div>

<div class="mfs-card p-3">
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead><tr><th>Code</th><th>Loan</th><th>Installment</th><th>Amount</th><th>Date</th><th>Method</th><th>Reference</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e($p['payment_code']) ?></td>
                    <td><?= e($p['loan_code']) ?></td>
                    <td>#<?= (int) $p['installment_no'] ?></td>
                    <td><?= money($p['amount_paid']) ?></td>
                    <td><?= display_date($p['payment_date']) ?></td>
                    <td><?= e(ucwords(str_replace('_', ' ', $p['payment_method']))) ?></td>
                    <td><?= e($p['reference_no'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
