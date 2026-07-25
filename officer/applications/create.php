<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_OFFICER);

$pdo = Database::getConnection();
$user = current_user();

$customers = $pdo->query(
    "SELECT c.customer_id, c.customer_code, u.full_name
     FROM customers c INNER JOIN users u ON u.user_id = c.user_id
     WHERE c.status = 'active' ORDER BY u.full_name ASC"
)->fetchAll();

$loanTypes = $pdo->query("SELECT * FROM loan_types WHERE status = 'active' ORDER BY type_name ASC")->fetchAll();

$errors = [];
$old = [
    'customer_id' => (string) (int) ($_GET['customer_id'] ?? 0),
    'loan_type_id' => '', 'requested_amount' => '', 'requested_duration_months' => '', 'purpose' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $key => $default) {
        $old[$key] = clean($_POST[$key] ?? $default);
    }

    $customerId = (int) $old['customer_id'];
    $loanTypeId = (int) $old['loan_type_id'];

    if ($customerId <= 0) $errors[] = 'Please select a customer.';
    if ($loanTypeId <= 0) $errors[] = 'Please select a loan type.';
    if (!is_numeric($old['requested_amount']) || (float) $old['requested_amount'] <= 0) $errors[] = 'Enter a valid requested amount.';
    if (!ctype_digit($old['requested_duration_months']) || (int) $old['requested_duration_months'] <= 0) $errors[] = 'Enter a valid duration in months.';

    $loanType = null;
    if (empty($errors)) {
        $ltStmt = $pdo->prepare("SELECT * FROM loan_types WHERE loan_type_id = :id AND status = 'active'");
        $ltStmt->execute(['id' => $loanTypeId]);
        $loanType = $ltStmt->fetch();

        if (!$loanType) {
            $errors[] = 'The selected loan type is not available.';
        } else {
            $amount = (float) $old['requested_amount'];
            $duration = (int) $old['requested_duration_months'];

            if ($amount < (float) $loanType['min_amount'] || $amount > (float) $loanType['max_amount']) {
                $errors[] = "Requested amount must be between " . money($loanType['min_amount']) . " and " . money($loanType['max_amount']) . " for this loan type.";
            }
            if ($duration < (int) $loanType['min_duration_months'] || $duration > (int) $loanType['max_duration_months']) {
                $errors[] = "Duration must be between {$loanType['min_duration_months']} and {$loanType['max_duration_months']} months for this loan type.";
            }
        }
    }

    if (empty($errors)) {
        $custCheck = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE customer_id = :id AND status = 'active'");
        $custCheck->execute(['id' => $customerId]);
        if ((int) $custCheck->fetchColumn() === 0) {
            $errors[] = 'The selected customer is not active.';
        }
    }

    if (empty($errors)) {
        $code = generate_code('loan_applications', 'application_code', 'APP', 6);
        $stmt = $pdo->prepare(
            'INSERT INTO loan_applications (application_code, customer_id, loan_type_id, requested_amount, requested_duration_months, purpose, status, submitted_by)
             VALUES (:code, :customer_id, :loan_type_id, :amount, :duration, :purpose, "pending", :submitted_by)'
        );
        $stmt->execute([
            'code' => $code, 'customer_id' => $customerId, 'loan_type_id' => $loanTypeId,
            'amount' => $old['requested_amount'], 'duration' => $old['requested_duration_months'],
            'purpose' => $old['purpose'] ?: null, 'submitted_by' => $user['user_id'],
        ]);
        app_log("Officer {$user['username']} submitted application {$code}.");
        flash('success', "Application {$code} submitted for review.");
        redirect('/officer/applications/index.php');
    }
}

$pageTitle = 'New Loan Application';
$activeMenu = 'applications';
$breadcrumb = ['Applications' => '/officer/applications/index.php', 'New' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="needs-validation" novalidate style="max-width: 720px;">
    <?php csrf_field(); ?>
    <div class="mfs-card p-3">
        <div class="mb-3">
            <label class="form-label">Customer</label>
            <select name="customer_id" class="form-select" required>
                <option value="">Select a customer...</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= (int) $c['customer_id'] ?>" <?= $old['customer_id'] === (string) $c['customer_id'] ? 'selected' : '' ?>>
                        <?= e($c['customer_code']) ?> — <?= e($c['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Loan Type</label>
            <select name="loan_type_id" id="loanTypeSelect" class="form-select" required>
                <option value="">Select a loan type...</option>
                <?php foreach ($loanTypes as $lt): ?>
                    <option value="<?= (int) $lt['loan_type_id'] ?>"
                        data-min-amount="<?= e($lt['min_amount']) ?>" data-max-amount="<?= e($lt['max_amount']) ?>"
                        data-min-duration="<?= (int) $lt['min_duration_months'] ?>" data-max-duration="<?= (int) $lt['max_duration_months'] ?>"
                        data-rate="<?= e($lt['interest_rate']) ?>"
                        <?= $old['loan_type_id'] === (string) $lt['loan_type_id'] ? 'selected' : '' ?>>
                        <?= e($lt['type_name']) ?> (<?= number_format((float) $lt['interest_rate'], 2) ?>% p.a.)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text" id="loanTypeHint">Amount and duration limits will appear here.</div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Requested Amount</label>
                <input type="number" step="0.01" id="amountInput" name="requested_amount" class="form-control" required value="<?= e($old['requested_amount']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Requested Duration (months)</label>
                <input type="number" id="durationInput" name="requested_duration_months" class="form-control" required value="<?= e($old['requested_duration_months']) ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Purpose</label>
            <textarea name="purpose" class="form-control" rows="2"><?= e($old['purpose']) ?></textarea>
        </div>
        <div class="alert alert-light border small" id="emiPreview" style="display:none;"></div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-brass px-4">Submit Application</button>
        <a href="<?= e(BASE_URL) ?>/officer/applications/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script>
(function () {
    var select = document.getElementById('loanTypeSelect');
    var hint = document.getElementById('loanTypeHint');
    var amountInput = document.getElementById('amountInput');
    var durationInput = document.getElementById('durationInput');
    var preview = document.getElementById('emiPreview');

    function updateHint() {
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) { hint.textContent = 'Amount and duration limits will appear here.'; return; }
        hint.textContent = 'Allowed amount: ' + opt.dataset.minAmount + '–' + opt.dataset.maxAmount +
            ' | Allowed duration: ' + opt.dataset.minDuration + '–' + opt.dataset.maxDuration + ' months';
        updatePreview();
    }

    function updatePreview() {
        var opt = select.options[select.selectedIndex];
        var amount = parseFloat(amountInput.value);
        var duration = parseInt(durationInput.value, 10);
        if (!opt || !opt.value || !amount || !duration) { preview.style.display = 'none'; return; }
        var emi = window.mfsCalculateEmi(amount, parseFloat(opt.dataset.rate), duration);
        preview.style.display = 'block';
        preview.textContent = 'Estimated EMI: ' + emi.toFixed(2) + ' per month for ' + duration + ' months (indicative only).';
    }

    select.addEventListener('change', updateHint);
    amountInput.addEventListener('input', updatePreview);
    durationInput.addEventListener('input', updatePreview);
    updateHint();
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
