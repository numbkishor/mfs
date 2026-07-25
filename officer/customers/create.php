<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_OFFICER);

$errors = [];
$old = [
    'full_name' => '', 'email' => '', 'phone' => '', 'username' => '',
    'national_id' => '', 'date_of_birth' => '', 'gender' => 'male',
    'address_line' => '', 'city' => '', 'occupation' => '', 'monthly_income' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $key => $default) {
        $old[$key] = clean($_POST[$key] ?? $default);
    }
    $password = (string) ($_POST['password'] ?? '');

    if ($old['full_name'] === '') $errors[] = 'Full name is required.';
    if ($old['username'] === '') $errors[] = 'Username is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if ($old['national_id'] === '') $errors[] = 'National ID is required.';
    if ($old['date_of_birth'] === '' || strtotime($old['date_of_birth']) === false) $errors[] = 'A valid date of birth is required.';
    if ($old['address_line'] === '') $errors[] = 'Address is required.';
    if ($old['city'] === '') $errors[] = 'City is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($old['monthly_income'] !== '' && !is_numeric($old['monthly_income'])) $errors[] = 'Monthly income must be numeric.';

    $pdo = Database::getConnection();

    if (empty($errors)) {
        $dupStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u OR email = :e');
        $dupStmt->execute(['u' => $old['username'], 'e' => $old['email']]);
        if ((int) $dupStmt->fetchColumn() > 0) {
            $errors[] = 'That username or email is already in use.';
        }

        $dupNid = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE national_id = :n');
        $dupNid->execute(['n' => $old['national_id']]);
        if ((int) $dupNid->fetchColumn() > 0) {
            $errors[] = 'A customer with that National ID already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $user = current_user();

            $userStmt = $pdo->prepare(
                'INSERT INTO users (role_id, full_name, username, email, phone, password_hash, status, created_by)
                 VALUES (:role_id, :full_name, :username, :email, :phone, :password_hash, :status, :created_by)'
            );
            $userStmt->execute([
                'role_id'       => ROLE_CUSTOMER,
                'full_name'     => $old['full_name'],
                'username'      => $old['username'],
                'email'         => $old['email'],
                'phone'         => $old['phone'] ?: null,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'status'        => 'active',
                'created_by'    => $user['user_id'],
            ]);
            $newUserId = (int) $pdo->lastInsertId();

            $customerCode = generate_code('customers', 'customer_code', 'CUS');

            $custStmt = $pdo->prepare(
                'INSERT INTO customers (user_id, customer_code, national_id, date_of_birth, gender, address_line, city, occupation, monthly_income, status, registered_by)
                 VALUES (:user_id, :code, :nid, :dob, :gender, :address, :city, :occupation, :income, :status, :registered_by)'
            );
            $custStmt->execute([
                'user_id'      => $newUserId,
                'code'         => $customerCode,
                'nid'          => $old['national_id'],
                'dob'          => $old['date_of_birth'],
                'gender'       => $old['gender'],
                'address'      => $old['address_line'],
                'city'         => $old['city'],
                'occupation'   => $old['occupation'] ?: null,
                'income'       => $old['monthly_income'] !== '' ? $old['monthly_income'] : null,
                'status'       => 'active',
                'registered_by'=> $user['user_id'],
            ]);

            $pdo->commit();
            app_log("Officer {$user['username']} registered customer {$customerCode}.");
            flash('success', "Customer {$customerCode} was registered successfully.");
            redirect('/officer/customers/index.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            $errors[] = 'Something went wrong while saving the customer. Please try again.';
        }
    }
}

$pageTitle = 'Register Customer';
$activeMenu = 'customers';
$breadcrumb = ['Customers' => '/officer/customers/index.php', 'New' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="needs-validation" novalidate>
    <?php csrf_field(); ?>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="mfs-card p-3">
                <h2 class="h6 mb-3">Account Details</h2>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required value="<?= e($old['full_name']) ?>">
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
        </div>
        <div class="col-lg-6">
            <div class="mfs-card p-3">
                <h2 class="h6 mb-3">Profile Details</h2>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">National ID</label>
                        <input type="text" name="national_id" class="form-control" required value="<?= e($old['national_id']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" required value="<?= e($old['date_of_birth']) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="male" <?= $old['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= $old['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= $old['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="address_line" class="form-control" required value="<?= e($old['address_line']) ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" required value="<?= e($old['city']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="occupation" class="form-control" value="<?= e($old['occupation']) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Monthly Income</label>
                    <input type="number" step="0.01" name="monthly_income" class="form-control" value="<?= e($old['monthly_income']) ?>">
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-brass px-4">Register Customer</button>
        <a href="<?= e(BASE_URL) ?>/officer/customers/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
