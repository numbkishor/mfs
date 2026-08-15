<?php

declare(strict_types=1);

/**
 * Shared loan-type form, included by create.php and edit.php.
 *
 * Expects:
 *   $old         (array)  current field values
 *   $errors      (array)  validation messages
 *   $showStatus  (bool)   render the active/inactive select (edit only)
 *   $submitLabel (string) text for the submit button
 */

$showStatus  = $showStatus ?? false;
$submitLabel = $submitLabel ?? 'Save Loan Type';
$errors      = $errors ?? [];
?>
<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger" role="alert"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="needs-validation mfs-form" novalidate>
    <?php csrf_field(); ?>
    <div class="mfs-card mfs-card-pad" style="max-width: 760px;">
        <div class="mfs-form-grid">
            <div class="mfs-field span-2">
                <label class="form-label" for="type_name">Type Name</label>
                <input type="text" id="type_name" name="type_name" class="form-control" required
                       value="<?= e($old['type_name']) ?>">
                <div class="invalid-feedback">Type name is required.</div>
            </div>

            <div class="mfs-field span-2">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="2"><?= e($old['description']) ?></textarea>
            </div>

            <div class="mfs-field">
                <label class="form-label" for="interest_rate">Interest Rate (% annual)</label>
                <input type="number" step="0.01" min="0" id="interest_rate" name="interest_rate"
                       class="form-control" required value="<?= e($old['interest_rate']) ?>">
                <div class="invalid-feedback">Enter an annual interest rate.</div>
            </div>

            <div class="mfs-field">
                <label class="form-label" for="processing_fee_pct">Processing Fee (%)</label>
                <input type="number" step="0.01" min="0" id="processing_fee_pct" name="processing_fee_pct"
                       class="form-control" value="<?= e($old['processing_fee_pct']) ?>">
            </div>

            <div class="mfs-field">
                <label class="form-label" for="min_amount">Minimum Amount</label>
                <input type="number" step="0.01" min="0" id="min_amount" name="min_amount"
                       class="form-control" required value="<?= e($old['min_amount']) ?>">
                <div class="invalid-feedback">Enter a minimum amount.</div>
            </div>

            <div class="mfs-field">
                <label class="form-label" for="max_amount">Maximum Amount</label>
                <input type="number" step="0.01" min="0" id="max_amount" name="max_amount"
                       class="form-control" required value="<?= e($old['max_amount']) ?>">
                <div class="invalid-feedback">Enter a maximum amount.</div>
            </div>

            <div class="mfs-field">
                <label class="form-label" for="min_duration_months">Minimum Duration (months)</label>
                <input type="number" min="1" step="1" id="min_duration_months" name="min_duration_months"
                       class="form-control" required value="<?= e($old['min_duration_months']) ?>">
                <div class="invalid-feedback">Enter a whole number of months.</div>
            </div>

            <div class="mfs-field">
                <label class="form-label" for="max_duration_months">Maximum Duration (months)</label>
                <input type="number" min="1" step="1" id="max_duration_months" name="max_duration_months"
                       class="form-control" required value="<?= e($old['max_duration_months']) ?>">
                <div class="invalid-feedback">Enter a whole number of months.</div>
            </div>

            <?php if ($showStatus): ?>
                <div class="mfs-field">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="active" <?= ($old['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($old['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mfs-form-actions">
        <button type="submit" class="btn btn-brass px-4"><?= e($submitLabel) ?></button>
        <a href="<?= e(BASE_URL) ?>/admin/loan_types/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
