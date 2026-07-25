<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_CASHIER);
refresh_overdue_statuses();

$loanId = (int) ($_GET['loan_id'] ?? $_POST['loan_id'] ?? 0);
$pdo = Database::getConnection();
$user = current_user();

$loanStmt = $pdo->prepare(
    "SELECT l.*, uc.full_name AS customer_name FROM loans l
     INNER JOIN customers c ON c.customer_id = l.customer_id INNER JOIN users uc ON uc.user_id = c.user_id
     WHERE l.loan_id = :id"
);
$loanStmt->execute(['id' => $loanId]);
$loan = $loanStmt->fetch();

if (!$loan) {
    flash('danger', 'Loan not found.');
    redirect('/cashier/repayments/index.php');
}

// A repayment can only be collected once the loan has been disbursed.
$disbCheck = $pdo->prepare('SELECT COUNT(*) FROM disbursements WHERE loan_id = :id');
$disbCheck->execute(['id' => $loanId]);
if ((int) $disbCheck->fetchColumn() === 0) {
    flash('warning', 'This loan has not been disbursed yet. Disburse it before collecting repayments.');
    redirect('/cashier/disbursements/disburse.php?id=' . $loanId);
}

$scheduleId = (int) ($_GET['schedule_id'] ?? $_POST['schedule_id'] ?? 0);

if ($scheduleId > 0) {
    $lineStmt = $pdo->prepare('SELECT * FROM repayment_schedules WHERE schedule_id = :id AND loan_id = :loan_id');
    $lineStmt->execute(['id' => $scheduleId, 'loan_id' => $loanId]);
    $line = $lineStmt->fetch();
} else {
    $lineStmt = $pdo->prepare(
        "SELECT * FROM repayment_schedules WHERE loan_id = :loan_id AND status IN ('pending','partial','overdue')
         ORDER BY due_date ASC LIMIT 1"
    );
    $lineStmt->execute(['loan_id' => $loanId]);
    $line = $lineStmt->fetch();
}

if (!$line) {
    flash('info', 'There is no outstanding installment to collect for this loan.');
    redirect('/cashier/loans/view.php?id=' . $loanId);
}

$remaining = round((float) $line['total_due'] - (float) $line['amount_paid'], 2);
$errors = [];
$old = ['amount_paid' => (string) $remaining, 'payment_date' => date('Y-m-d'), 'payment_method' => 'cash', 'reference_no' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $key => $default) {
        $old[$key] = clean($_POST[$key] ?? $default);
    }

    if (!is_numeric($old['amount_paid']) || (float) $old['amount_paid'] <= 0) {
        $errors[] = 'Enter a valid payment amount.';
    } elseif ((float) $old['amount_paid'] > $remaining + 0.01) {
        $errors[] = 'Payment amount cannot exceed the remaining due of ' . money($remaining) . ' for this installment.';
    }
    if (strtotime($old['payment_date']) === false) $errors[] = 'Enter a valid payment date.';
    if (!in_array($old['payment_method'], ['cash', 'bank_transfer', 'mobile_wallet', 'cheque'], true)) $errors[] = 'Select a valid payment method.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $paymentCode = generate_code('payments', 'payment_code', 'PAY', 6);
            $insert = $pdo->prepare(
                'INSERT INTO payments (payment_code, loan_id, schedule_id, amount_paid, payment_date, payment_method, reference_no, received_by, remarks)
                 VALUES (:code, :loan_id, :schedule_id, :amount, :date, :method, :ref, :by, :remarks)'
            );
            $insert->execute([
                'code' => $paymentCode, 'loan_id' => $loanId, 'schedule_id' => $line['schedule_id'],
                'amount' => $old['amount_paid'], 'date' => $old['payment_date'], 'method' => $old['payment_method'],
                'ref' => $old['reference_no'] ?: null, 'by' => $user['user_id'], 'remarks' => clean($_POST['remarks'] ?? '') ?: null,
            ]);

            $newPaid = round((float) $line['amount_paid'] + (float) $old['amount_paid'], 2);
            $newStatus = schedule_line_status((float) $line['total_due'], $newPaid, $line['due_date']);

            $update = $pdo->prepare('UPDATE repayment_schedules SET amount_paid = :paid, status = :status WHERE schedule_id = :id');
            $update->execute(['paid' => $newPaid, 'status' => $newStatus, 'id' => $line['schedule_id']]);

            // If every installment on the loan is now fully paid, close the loan out.
            $remainingCount = $pdo->prepare("SELECT COUNT(*) FROM repayment_schedules WHERE loan_id = :id AND status != 'paid'");
            $remainingCount->execute(['id' => $loanId]);
            if ((int) $remainingCount->fetchColumn() === 0) {
                $pdo->prepare("UPDATE loans SET status = 'closed' WHERE loan_id = :id")->execute(['id' => $loanId]);
            }

            $pdo->commit();
            app_log("Cashier {$user['username']} recorded payment {$paymentCode} for loan {$loan['loan_code']}.");
            flash('success', "Payment {$paymentCode} recorded.");
            redirect('/cashier/loans/view.php?id=' . $loanId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            $errors[] = 'Something went wrong while recording the payment. Please try again.';
        }
    }
}

$pageTitle = 'Collect Payment';
$activeMenu = 'repayments';
$breadcrumb = ['Repayments' => '/cashier/repayments/index.php', $loan['loan_code'] => '/cashier/loans/view.php?id=' . $loanId, 'Collect Payment' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="mfs-card p-3" style="max-width: 640px;">
    <dl class="row small mb-3">
        <dt class="col-5">Loan</dt><dd class="col-7"><?= e($loan['loan_code']) ?></dd>
        <dt class="col-5">Customer</dt><dd class="col-7"><?= e($loan['customer_name']) ?></dd>
        <dt class="col-5">Installment #</dt><dd class="col-7"><?= (int) $line['installment_no'] ?> — due <?= display_date($line['due_date']) ?></dd>
        <dt class="col-5">Already Paid</dt><dd class="col-7"><?= money($line['amount_paid']) ?> of <?= money($line['total_due']) ?></dd>
        <dt class="col-5">Remaining Due</dt><dd class="col-7 fw-semibold"><?= money($remaining) ?></dd>
    </dl>
    <form method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="loan_id" value="<?= $loanId ?>">
        <input type="hidden" name="schedule_id" value="<?= (int) $line['schedule_id'] ?>">
        <div class="mb-3">
            <label class="form-label">Amount</label>
            <input type="number" step="0.01" name="amount_paid" class="form-control" required value="<?= e($old['amount_paid']) ?>">
            <div class="form-text">Enter less than the full amount to record a partial payment.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Payment Date</label>
            <input type="date" name="payment_date" class="form-control" required value="<?= e($old['payment_date']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Method</label>
            <select name="payment_method" class="form-select">
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="mobile_wallet">Mobile Wallet</option>
                <option value="cheque">Cheque</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Reference No.</label>
            <input type="text" name="reference_no" class="form-control" value="<?= e($old['reference_no']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Remarks</label>
            <textarea name="remarks" class="form-control" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-brass px-4">Record Payment</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
