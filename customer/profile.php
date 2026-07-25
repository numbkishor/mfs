<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login('customer');
require_role(ROLE_CUSTOMER);

$user = current_user();
$pdo = Database::getConnection();

$stmt = $pdo->prepare(
    'SELECT c.*, u.full_name, u.email, u.phone, u.username
     FROM customers c INNER JOIN users u ON u.user_id = c.user_id
     WHERE c.customer_id = :id'
);
$stmt->execute(['id' => $user['customer_id']]);
$customer = $stmt->fetch();

$pageTitle = 'My Profile';
$activeMenu = 'profile';
$breadcrumb = ['My Profile' => null];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mfs-card p-3" style="max-width: 560px;">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <h2 class="h5 mb-0"><?= e($customer['full_name']) ?></h2>
        <?= status_badge($customer['status']) ?>
    </div>
    <dl class="row small mb-0">
        <dt class="col-5">Customer Code</dt><dd class="col-7"><?= e($customer['customer_code']) ?></dd>
        <dt class="col-5">Username</dt><dd class="col-7"><?= e($customer['username']) ?></dd>
        <dt class="col-5">Email</dt><dd class="col-7"><?= e($customer['email']) ?></dd>
        <dt class="col-5">Phone</dt><dd class="col-7"><?= e($customer['phone'] ?? '—') ?></dd>
        <dt class="col-5">National ID</dt><dd class="col-7"><?= e($customer['national_id']) ?></dd>
        <dt class="col-5">Date of Birth</dt><dd class="col-7"><?= display_date($customer['date_of_birth']) ?></dd>
        <dt class="col-5">Gender</dt><dd class="col-7"><?= e(ucfirst($customer['gender'])) ?></dd>
        <dt class="col-5">Address</dt><dd class="col-7"><?= e($customer['address_line']) ?>, <?= e($customer['city']) ?></dd>
        <dt class="col-5">Occupation</dt><dd class="col-7"><?= e($customer['occupation'] ?? '—') ?></dd>
    </dl>
    <p class="text-muted small mt-3 mb-0">To update any of these details, please contact your loan officer.</p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
