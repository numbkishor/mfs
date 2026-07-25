<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$pdo = Database::getConnection();
$roles = $pdo->query('SELECT role_id, role_name FROM roles WHERE role_id IN (1,2,3,5) ORDER BY role_id')->fetchAll();

$errors = [];
$old = ['full_name' => '', 'username' => '', 'email' => '', 'phone' => '', 'role_id' => (string) ROLE_OFFICER];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $key => $default) {
        $old[$key] = clean($_POST[$key] ?? $default);
    }
    $password = (string) ($_POST['password'] ?? '');

    if ($old['full_name'] === '') $errors[] = 'Full name is required.';
    if ($old['username'] === '') $errors[] = 'Username is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (!in_array((int) $old['role_id'], [ROLE_ADMIN, ROLE_MANAGER, ROLE_OFFICER, ROLE_CASHIER], true)) $errors[] = 'Please select a valid role.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u OR email = :e');
        $dup->execute(['u' => $old['username'], 'e' => $old['email']]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors[] = 'That username or email is already in use.';
        }
    }

    if (empty($errors)) {
        $admin = current_user();
        $stmt = $pdo->prepare(
            'INSERT INTO users (role_id, full_name, username, email, phone, password_hash, status, created_by)
             VALUES (:role_id, :name, :username, :email, :phone, :hash, "active", :created_by)'
        );
        $stmt->execute([
            'role_id' => (int) $old['role_id'], 'name' => $old['full_name'], 'username' => $old['username'],
            'email' => $old['email'], 'phone' => $old['phone'] ?: null,
            'hash' => password_hash($password, PASSWORD_BCRYPT), 'created_by' => $admin['user_id'],
        ]);
        app_log("Admin {$admin['username']} created employee account {$old['username']}.");
        flash('success', 'Employee account created.');
        redirect('/admin/users/index.php');
    }
}

$pageTitle = 'New Employee';
$activeMenu = 'users';
$breadcrumb = ['Employee Accounts' => '/admin/users/index.php', 'New' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="needs-validation" novalidate style="max-width: 560px;">
    <?php csrf_field(); ?>
    <div class="mfs-card p-3">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required value="<?= e($old['full_name']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role_id" class="form-select" required>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= (int) $r['role_id'] ?>" <?= $old['role_id'] === (string) $r['role_id'] ? 'selected' : '' ?>><?= e($r['role_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required value="<?= e($old['username']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Temporary Password</label>
                <input type="text" name="password" class="form-control" required minlength="6" placeholder="Min. 6 characters">
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
    </div>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-brass px-4">Create Account</button>
        <a href="<?= e(BASE_URL) ?>/admin/users/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
