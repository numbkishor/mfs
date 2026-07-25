<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$pdo = Database::getConnection();
$rows = $pdo->query(
    "SELECT u.user_id, u.full_name, u.username, u.email, u.phone, u.status, u.last_login_at, r.role_name
     FROM users u INNER JOIN roles r ON r.role_id = u.role_id
     WHERE u.role_id IN (1,2,3,5)
     ORDER BY r.role_id ASC, u.full_name ASC"
)->fetchAll();

$pageTitle = 'Employee Accounts';
$activeMenu = 'users';
$breadcrumb = ['Employee Accounts' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= e(BASE_URL) ?>/admin/users/create.php" class="btn btn-brass"><i class="bi bi-person-plus"></i> New Employee</a>
</div>

<div class="mfs-card p-3">
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Email</th><th>Phone</th><th>Status</th><th>Last Login</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['username']) ?></td>
                    <td><span class="badge bg-secondary"><?= e($row['role_name']) ?></span></td>
                    <td><?= e($row['email']) ?></td>
                    <td><?= e($row['phone'] ?? '—') ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td><?= $row['last_login_at'] ? display_date($row['last_login_at'], 'd M Y H:i') : 'Never' ?></td>
                    <td class="text-end">
                        <a href="<?= e(BASE_URL) ?>/admin/users/edit.php?id=<?= (int) $row['user_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <?php if ((int) $row['user_id'] !== current_user()['user_id']): ?>
                            <a href="<?= e(BASE_URL) ?>/admin/users/delete.php?id=<?= (int) $row['user_id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Suspend this employee account?"><i class="bi bi-slash-circle"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
