<?php
/** Expects: $rows (array), $basePath (string) */
?>
<div class="table-responsive">
    <table class="table mfs-table align-middle">
        <thead>
        <tr><th>Loan</th><th>Customer</th><th>Type</th><th>Principal</th><th>Outstanding</th><th>Status</th><th>Start</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['loan_code']) ?></td>
                <td><?= e($row['customer_name']) ?> <span class="text-muted">(<?= e($row['customer_code']) ?>)</span></td>
                <td><?= e($row['type_name']) ?></td>
                <td><?= money($row['principal_amount']) ?></td>
                <td><?= money(get_loan_outstanding_balance((int) $row['loan_id'])) ?></td>
                <td><?= status_badge($row['status']) ?></td>
                <td><?= display_date($row['start_date']) ?></td>
                <td class="text-end"><a href="<?= e(BASE_URL . $basePath) ?>/view.php?id=<?= (int) $row['loan_id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No loans found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
