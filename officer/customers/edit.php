<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_OFFICER);

$customerId = (int) ($_GET['id'] ?? 0);
$pdo = Database::getConnection();

$stmt = $pdo->prepare(
    'SELECT c.*, u.full_name, u.email, u.phone, u.username
     FROM customers c INNER JOIN users u ON u.user_id = c.user_id
     WHERE c.customer_id = :id'
);
$stmt->execute(['id' => $customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    flash('danger', 'Customer not found.');
    redirect('/officer/customers/index.php');
}

$errors = [];
$old = $customer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (['full_name', 'email', 'phone', 'address_line', 'city', 'occupation', 'monthly_income', 'gender', 'date_of_birth', 'status'] as $field) {
        $old[$field] = clean($_POST[$field] ?? ($old[$field] ?? ''));
    }

    if ($old['full_name'] === '') $errors[] = 'Full name is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if ($old['address_line'] === '') $errors[] = 'Address is required.';
    if ($old['city'] === '') $errors[] = 'City is required.';
    if ($old['monthly_income'] !== '' && !is_numeric($old['monthly_income'])) $errors[] = 'Monthly income must be numeric.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $updateUser = $pdo->prepare('UPDATE users SET full_name = :name, email = :email, phone = :phone WHERE user_id = :uid');
            $updateUser->execute([
                'name' => $old['full_name'], 'email' => $old['email'], 'phone' => $old['phone'] ?: null, 'uid' => $customer['user_id'],
            ]);

            $updateCust = $pdo->prepare(
                'UPDATE customers SET date_of_birth = :dob, gender = :gender, address_line = :address, city = :city,
                 occupation = :occupation, monthly_income = :income, status = :status
                 WHERE customer_id = :id'
            );
            $updateCust->execute([
                'dob' => $old['date_of_birth'], 'gender' => $old['gender'], 'address' => $old['address_line'], 'city' => $old['city'],
                'occupation' => $old['occupation'] ?: null, 'income' => $old['monthly_income'] !== '' ? $old['monthly_income'] : null,
                'status' => $old['status'], 'id' => $customerId,
            ]);

            $pdo->commit();
            flash('success', 'Customer profile updated.');
            redirect('/officer/customers/view.php?id=' . $customerId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            $errors[] = 'Something went wrong while saving. Please try again.';
        }
    }
}

$pageTitle = 'Edit Customer';
$activeMenu = 'customers';
$breadcrumb = ['Customers' => '/officer/customers/index.php', $customer['customer_code'] => '/officer/customers/view.php?id=' . $customerId, 'Edit' => null];
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
                <p class="text-muted small">Username <code><?= e($customer['username']) ?></code> cannot be changed here.</p>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required value="<?= e($old['full_name']) ?>">
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
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $old['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $old['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="blacklisted" <?= $old['status'] === 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="mfs-card p-3">
                <h2 class="h6 mb-3">Profile Details</h2>
                <p class="text-muted small">National ID <code><?= e($customer['national_id']) ?></code> cannot be changed here.</p>
                <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" required value="<?= e($old['date_of_birth']) ?>">
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
        <button type="submit" class="btn btn-brass px-4">Save Changes</button>
        <a href="<?= e(BASE_URL) ?>/officer/customers/view.php?id=<?= $customerId ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
