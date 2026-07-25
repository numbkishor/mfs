<?php
/** Expects $old (array), $errors (array). Included by create.php / edit.php. */
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
        <button type="submit" class="btn btn-brass px-4">Save Loan Type</button>
        <a href="<?= e(BASE_URL) ?>/admin/loan_types/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
