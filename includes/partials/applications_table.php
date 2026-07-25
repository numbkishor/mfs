<?php
/**
 * Expects: $rows (array), $basePath (string), $showOfficer (bool),
 *          $showReview (bool) — link to review.php instead of view.php for pending rows
 */
$showOfficer = $showOfficer ?? true;
$showReview = $showReview ?? false;
?>
<div class="table-responsive">
    <table class="table mfs-table align-middle">
        <thead>
        <tr>
            <th>Code</th>
            <th>Customer</th>
            <th>Loan Type</th>
            <th>Amount</th>
            <th>Duration</th>
            <?php if ($showOfficer): ?><th>Officer</th><?php endif; ?>
            <th>Status</th>
            <th>Date</th>
            <th class="text-end">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['application_code']) ?></td>
                <td><?= e($row['customer_name']) ?></td>
                <td><?= e($row['type_name']) ?></td>
                <td><?= money($row['requested_amount']) ?></td>
                <td><?= (int) $row['requested_duration_months'] ?> mo</td>
                <?php if ($showOfficer): ?><td><?= e($row['officer_name']) ?></td><?php endif; ?>
                <td><?= status_badge($row['status']) ?></td>
                <td><?= display_date($row['created_at']) ?></td>
                <td class="text-end">
                    <?php if ($showReview && $row['status'] === 'pending'): ?>
                        <a href="<?= e(BASE_URL . $basePath) ?>/review.php?id=<?= (int) $row['application_id'] ?>" class="btn btn-sm btn-brass">Review</a>
                    <?php else: ?>
                        <a href="<?= e(BASE_URL . $basePath) ?>/view.php?id=<?= (int) $row['application_id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No applications found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
