<?php
/**
 * Expects: $rows (array), $basePath (string, e.g. '/officer/customers'),
 *          $canEdit (bool), $canDelete (bool)
 */
$canEdit = $canEdit ?? false;
$canDelete = $canDelete ?? false;
?>
<div class="table-responsive">
    <table class="table mfs-table align-middle">
        <thead>
        <tr>
            <th>Code</th>
            <th>Name</th>
            <th>National ID</th>
            <th>Phone</th>
            <th>City</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['customer_code']) ?></td>
                <td><?= e($row['full_name']) ?></td>
                <td><?= e($row['national_id']) ?></td>
                <td><?= e($row['phone'] ?? '—') ?></td>
                <td><?= e($row['city']) ?></td>
                <td><?= status_badge($row['status']) ?></td>
                <td class="text-end">
                    <a href="<?= e(BASE_URL . $basePath) ?>/view.php?id=<?= (int) $row['customer_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                    <?php if ($canEdit): ?>
                        <a href="<?= e(BASE_URL . $basePath) ?>/edit.php?id=<?= (int) $row['customer_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <?php endif; ?>
                    <?php if ($canDelete): ?>
                        <a href="<?= e(BASE_URL . $basePath) ?>/delete.php?id=<?= (int) $row['customer_id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Deactivate this customer?"><i class="bi bi-trash"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No customers found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
