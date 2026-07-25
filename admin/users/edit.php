<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::getConnection();

$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id AND role_id IN (1,2,3,5)');
$stmt->execute(['id' => $id]);
$employee = $stmt->fetch();

if (!$employee) {
    flash('danger', 'Employee not found.');
    redirect('/admin/users/index.php');
}

$roles = $pdo->query('SELECT role_id, role_name FROM roles WHERE role_id IN (1,2,3,5) ORDER BY role_id')->fetchAll();

$errors = [];
$old = $employee;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (['full_name', 'email', 'phone', 'role_id', 'status'] as $field) {
        $old[$field] = clean($_POST[$field] ?? ($old[$field] ?? ''));
    }
    $newPassword = (string) ($_POST['password'] ?? '');

    if ($old['full_name'] === '') $errors[] = 'Full name is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (!in_array((int) $old['role_id'], [ROLE_ADMIN, ROLE_MANAGER, ROLE_OFFICER, ROLE_CASHIER], true)) $errors[] = 'Please select a valid role.';
    if ($newPassword !== '' && strlen($newPassword) < 6) $errors[] = 'New password must be at least 6 characters.';

    if (empty($errors)) {
        if ($newPassword !== '') {
            $update = $pdo->prepare(
                'UPDATE users SET role_id=:role_id, full_name=:name, email=:email, phone=:phone, status=:status, password_hash=:hash WHERE user_id=:id'
            );
            $update->execute([
                'role_id' => (int) $old['role_id'], 'name' => $old['full_name'], 'email' => $old['email'],
                'phone' => $old['phone'] ?: null, 'status' => $old['status'],
                'hash' => password_hash($newPassword, PASSWORD_BCRYPT), 'id' => $id,
            ]);
        } else {
            $update = $pdo->prepare(
                'UPDATE users SET role_id=:role_id, full_name=:name, email=:email, phone=:phone, status=:status WHERE user_id=:id'
            );
            $update->execute([
                'role_id' => (int) $old['role_id'], 'name' => $old['full_name'], 'email' => $old['email'],
                'phone' => $old['phone'] ?: null, 'status' => $old['status'], 'id' => $id,
            ]);
        }

        flash('success', 'Employee account updated.');
        redirect('/admin/users/index.php');
    }
}

$pageTitle = 'Edit Employee';
$activeMenu = 'users';
$breadcrumb = ['Employee Accounts' => '/admin/users/index.php', $employee['full_name'] => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="needs-validation" novalidate style="max-width: 560px;">
    <?php csrf_field(); ?>
    <div class="mfs-card p-3">
        <p class="text-muted small">Username <code><?= e($employee['username']) ?></code> cannot be changed.</p>
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required value="<?= e($old['full_name']) ?>">
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int) $r['role_id'] ?>" <?= (string) $old['role_id'] === (string) $r['role_id'] ? 'selected' : '' ?>><?= e($r['role_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $old['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $old['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="suspended" <?= $old['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required value="<?= e($old['email']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= e($old['phone']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Reset Password</label>
            <input type="text" name="password" class="form-control" placeholder="Leave blank to keep current password">
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-brass px-4">Save Changes</button>
        <a href="<?= e(BASE_URL) ?>/admin/users/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
