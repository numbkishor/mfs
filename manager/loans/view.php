<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_MANAGER);
refresh_overdue_statuses();

$loanId = (int) ($_GET['id'] ?? 0);
$pdo = Database::getConnection();

$stmt = $pdo->prepare(
    "SELECT l.*, uc.full_name AS customer_name, c.customer_code, lt.type_name
     FROM loans l
     INNER JOIN customers c ON c.customer_id = l.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     INNER JOIN loan_types lt ON lt.loan_type_id = l.loan_type_id
     WHERE l.loan_id = :id"
);
$stmt->execute(['id' => $loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    flash('danger', 'Loan not found.');
    redirect('/manager/loans/index.php');
}

$disbStmt = $pdo->prepare('SELECT * FROM disbursements WHERE loan_id = :id');
$disbStmt->execute(['id' => $loanId]);
$disbursement = $disbStmt->fetch();

$scheduleStmt = $pdo->prepare('SELECT * FROM repayment_schedules WHERE loan_id = :id ORDER BY installment_no ASC');
$scheduleStmt->execute(['id' => $loanId]);
$schedule = $scheduleStmt->fetchAll();

$paymentsStmt = $pdo->prepare(
    "SELECT p.*, rs.installment_no FROM payments p INNER JOIN repayment_schedules rs ON rs.schedule_id = p.schedule_id
     WHERE p.loan_id = :id ORDER BY p.payment_date DESC"
);
$paymentsStmt->execute(['id' => $loanId]);
$payments = $paymentsStmt->fetchAll();

$outstanding = get_loan_outstanding_balance($loanId);

$pageTitle = 'Loan ' . $loan['loan_code'];
$activeMenu = 'loans';
$breadcrumb = ['Loans' => '/manager/loans/index.php', $loan['loan_code'] => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="mfs-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h2 class="h5 mb-0"><?= e($loan['loan_code']) ?></h2>
                <?= status_badge($loan['status']) ?>
            </div>
            <dl class="row small mb-0">
                <dt class="col-5">Customer</dt><dd class="col-7"><?= e($loan['customer_name']) ?> (<?= e($loan['customer_code']) ?>)</dd>
                <dt class="col-5">Loan Type</dt><dd class="col-7"><?= e($loan['type_name']) ?></dd>
                <dt class="col-5">Principal</dt><dd class="col-7"><?= money($loan['principal_amount']) ?></dd>
                <dt class="col-5">Interest Rate</dt><dd class="col-7"><?= number_format((float) $loan['interest_rate'], 2) ?>% p.a.</dd>
                <dt class="col-5">EMI</dt><dd class="col-7"><?= money($loan['emi_amount']) ?></dd>
                <dt class="col-5">Duration</dt><dd class="col-7"><?= (int) $loan['duration_months'] ?> months</dd>
                <dt class="col-5">Start / End</dt><dd class="col-7"><?= display_date($loan['start_date']) ?> – <?= display_date($loan['end_date']) ?></dd>
                <dt class="col-5">Outstanding</dt><dd class="col-7 fw-semibold"><?= money($outstanding) ?></dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mfs-card p-3 h-100">
            <h2 class="h6 mb-2">Disbursement</h2>
            <?php if ($disbursement): ?>
                <dl class="row small mb-0">
                    <dt class="col-5">Amount</dt><dd class="col-7"><?= money($disbursement['disbursed_amount']) ?></dd>
                    <dt class="col-5">Date</dt><dd class="col-7"><?= display_date($disbursement['disbursement_date']) ?></dd>
                    <dt class="col-5">Method</dt><dd class="col-7"><?= e(ucwords(str_replace('_', ' ', $disbursement['method']))) ?></dd>
                    <dt class="col-5">Reference</dt><dd class="col-7"><?= e($disbursement['reference_no'] ?? '—') ?></dd>
                </dl>
            <?php else: ?>
                <p class="text-muted small">Not yet disbursed by the loan officer.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mfs-card p-3 mb-4">
    <h2 class="h6 mb-3">EMI Schedule</h2>
    <div class="table-responsive">
        <table class="table table-sm mfs-table align-middle">
            <thead><tr><th>#</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total Due</th><th>Paid</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($schedule as $line): ?>
                <tr>
                    <td><?= (int) $line['installment_no'] ?></td>
                    <td><?= display_date($line['due_date']) ?></td>
                    <td><?= money($line['principal_due']) ?></td>
                    <td><?= money($line['interest_due']) ?></td>
                    <td><?= money($line['total_due']) ?></td>
                    <td><?= money($line['amount_paid']) ?></td>
                    <td><?= status_badge($line['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mfs-card p-3">
    <h2 class="h6 mb-3">Payment History</h2>
    <div class="table-responsive">
        <table class="table table-sm mfs-table align-middle">
            <thead><tr><th>Code</th><th>Installment</th><th>Amount</th><th>Date</th><th>Method</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e($p['payment_code']) ?></td>
                    <td>#<?= (int) $p['installment_no'] ?></td>
                    <td><?= money($p['amount_paid']) ?></td>
                    <td><?= display_date($p['payment_date']) ?></td>
                    <td><?= e(ucwords(str_replace('_', ' ', $p['payment_method']))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No payments recorded yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
