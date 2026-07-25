<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::getConnection();

$stmt = $pdo->prepare('SELECT * FROM loan_types WHERE loan_type_id = :id');
$stmt->execute(['id' => $id]);
$loanType = $stmt->fetch();

if (!$loanType) {
    flash('danger', 'Loan type not found.');
    redirect('/admin/loan_types/index.php');
}

$errors = [];
$old = $loanType;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (['type_name', 'description', 'interest_rate', 'min_amount', 'max_amount', 'min_duration_months', 'max_duration_months', 'processing_fee_pct', 'status'] as $field) {
        $old[$field] = clean($_POST[$field] ?? ($old[$field] ?? ''));
    }

    if ($old['type_name'] === '') $errors[] = 'Type name is required.';
    foreach (['interest_rate', 'min_amount', 'max_amount', 'processing_fee_pct'] as $numField) {
        if (!is_numeric($old[$numField])) $errors[] = ucwords(str_replace('_', ' ', $numField)) . ' must be a number.';
    }
    foreach (['min_duration_months', 'max_duration_months'] as $intField) {
        if (!ctype_digit((string) $old[$intField])) $errors[] = ucwords(str_replace('_', ' ', $intField)) . ' must be a whole number.';
    }
    if (empty($errors) && (float) $old['max_amount'] < (float) $old['min_amount']) {
        $errors[] = 'Maximum amount must be greater than or equal to minimum amount.';
    }

    if (empty($errors)) {
        $update = $pdo->prepare(
            'UPDATE loan_types SET type_name=:name, description=:desc, interest_rate=:rate, min_amount=:min_amt,
             max_amount=:max_amt, min_duration_months=:min_dur, max_duration_months=:max_dur,
             processing_fee_pct=:fee, status=:status WHERE loan_type_id=:id'
        );
        $update->execute([
            'name' => $old['type_name'], 'desc' => $old['description'] ?: null, 'rate' => $old['interest_rate'],
            'min_amt' => $old['min_amount'], 'max_amt' => $old['max_amount'],
            'min_dur' => $old['min_duration_months'], 'max_dur' => $old['max_duration_months'],
            'fee' => $old['processing_fee_pct'], 'status' => $old['status'], 'id' => $id,
        ]);
        flash('success', 'Loan type updated.');
        redirect('/admin/loan_types/index.php');
    }
}

$pageTitle = 'Edit Loan Type';
$activeMenu = 'loan_types';
$breadcrumb = ['Loan Types' => '/admin/loan_types/index.php', $loanType['type_name'] => null];
require_once __DIR__ . '/../../includes/header.php';
?>
<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>
<form method="post" class="needs-validation" novalidate>
    <?php csrf_field(); ?>
    <div class="mfs-card p-3" style="max-width: 720px;">
        <div class="mb-3">
            <label class="form-label">Type Name</label>
            <input type="text" name="type_name" class="form-control" required value="<?= e($old['type_name']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"><?= e($old['description']) ?></textarea>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Interest Rate (% annual)</label>
                <input type="number" step="0.01" name="interest_rate" class="form-control" required value="<?= e($old['interest_rate']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Processing Fee (%)</label>
                <input type="number" step="0.01" name="processing_fee_pct" class="form-control" value="<?= e($old['processing_fee_pct']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $old['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $old['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Minimum Amount</label>
                <input type="number" step="0.01" name="min_amount" class="form-control" required value="<?= e($old['min_amount']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Maximum Amount</label>
                <input type="number" step="0.01" name="max_amount" class="form-control" required value="<?= e($old['max_amount']) ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Minimum Duration (months)</label>
                <input type="number" name="min_duration_months" class="form-control" required value="<?= e($old['min_duration_months']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Maximum Duration (months)</label>
                <input type="number" name="max_duration_months" class="form-control" required value="<?= e($old['max_duration_months']) ?>">
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-brass px-4">Save Changes</button>
        <a href="<?= e(BASE_URL) ?>/admin/loan_types/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
