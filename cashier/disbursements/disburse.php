<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_CASHIER);

$loanId = (int) ($_GET['id'] ?? $_POST['loan_id'] ?? 0);
$pdo = Database::getConnection();
$user = current_user();

$stmt = $pdo->prepare(
    "SELECT l.*, uc.full_name AS customer_name
     FROM loans l INNER JOIN customers c ON c.customer_id = l.customer_id INNER JOIN users uc ON uc.user_id = c.user_id
     WHERE l.loan_id = :id"
);
$stmt->execute(['id' => $loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    flash('danger', 'Loan not found.');
    redirect('/cashier/disbursements/index.php');
}

$already = $pdo->prepare('SELECT * FROM disbursements WHERE loan_id = :id');
$already->execute(['id' => $loanId]);
$disbursement = $already->fetch();

$errors = [];
$old = ['disbursed_amount' => (string) $loan['principal_amount'], 'disbursement_date' => date('Y-m-d'), 'method' => 'bank_transfer', 'reference_no' => ''];

if (!$disbursement && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $key => $default) {
        $old[$key] = clean($_POST[$key] ?? $default);
    }

    if (!is_numeric($old['disbursed_amount']) || (float) $old['disbursed_amount'] <= 0) $errors[] = 'Enter a valid disbursed amount.';
    if (strtotime($old['disbursement_date']) === false) $errors[] = 'Enter a valid disbursement date.';
    if (!in_array($old['method'], ['cash', 'bank_transfer', 'mobile_wallet', 'cheque'], true)) $errors[] = 'Select a valid method.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO disbursements (loan_id, disbursed_amount, disbursement_date, method, reference_no, disbursed_by, remarks)
             VALUES (:loan_id, :amount, :date, :method, :ref, :by, :remarks)'
        );
        $stmt->execute([
            'loan_id' => $loanId, 'amount' => $old['disbursed_amount'], 'date' => $old['disbursement_date'],
            'method' => $old['method'], 'ref' => $old['reference_no'] ?: null, 'by' => $user['user_id'],
            'remarks' => clean($_POST['remarks'] ?? '') ?: null,
        ]);
        app_log("Cashier {$user['username']} recorded disbursement for loan {$loan['loan_code']}.");
        flash('success', 'Disbursement recorded. Repayments can now be collected on this loan.');
        redirect('/cashier/loans/view.php?id=' . $loanId);
    }
}

$pageTitle = 'Record Disbursement';
$activeMenu = 'disbursements';
$breadcrumb = ['Disbursements' => '/cashier/disbursements/index.php', $loan['loan_code'] => '/cashier/loans/view.php?id=' . $loanId, 'Disburse' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="mfs-card p-3" style="max-width: 640px;">
    <dl class="row small mb-3">
        <dt class="col-5">Loan</dt><dd class="col-7"><?= e($loan['loan_code']) ?></dd>
        <dt class="col-5">Customer</dt><dd class="col-7"><?= e($loan['customer_name']) ?></dd>
        <dt class="col-5">Principal</dt><dd class="col-7"><?= money($loan['principal_amount']) ?></dd>
    </dl>

    <?php if ($disbursement): ?>
        <div class="alert alert-success">
            Disbursed <?= money($disbursement['disbursed_amount']) ?> via <?= e(ucwords(str_replace('_', ' ', $disbursement['method']))) ?>
            on <?= display_date($disbursement['disbursement_date']) ?>.
        </div>
        <a href="<?= e(BASE_URL) ?>/cashier/loans/view.php?id=<?= $loanId ?>" class="btn btn-outline-secondary">Back to Loan</a>
    <?php else: ?>
        <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="loan_id" value="<?= $loanId ?>">
            <div class="mb-3">
                <label class="form-label">Disbursed Amount</label>
                <input type="number" step="0.01" name="disbursed_amount" class="form-control" required value="<?= e($old['disbursed_amount']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Disbursement Date</label>
                <input type="date" name="disbursement_date" class="form-control" required value="<?= e($old['disbursement_date']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Method</label>
                <select name="method" class="form-select">
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
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
            <button type="submit" class="btn btn-brass px-4">Record Disbursement</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
