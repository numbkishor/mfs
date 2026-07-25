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

$apps = $pdo->prepare(
    "SELECT a.application_code, a.requested_amount, a.status, a.created_at, lt.type_name
     FROM loan_applications a INNER JOIN loan_types lt ON lt.loan_type_id = a.loan_type_id
     WHERE a.customer_id = ? ORDER BY a.created_at DESC"
);
$apps->execute([$customerId]);
$apps = $apps->fetchAll();

$loans = $pdo->prepare(
    "SELECT l.loan_id, l.loan_code, l.principal_amount, l.status, lt.type_name
     FROM loans l INNER JOIN loan_types lt ON lt.loan_type_id = l.loan_type_id
     WHERE l.customer_id = ? ORDER BY l.created_at DESC"
);
$loans->execute([$customerId]);
$loans = $loans->fetchAll();

$pageTitle = 'Customer Profile';
$activeMenu = 'customers';
$breadcrumb = ['Customers' => '/officer/customers/index.php', $customer['customer_code'] => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="mfs-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <h2 class="h5 mb-1"><?= e($customer['full_name']) ?></h2>
                <?= status_badge($customer['status']) ?>
            </div>
            <p class="text-muted small mb-3"><?= e($customer['customer_code']) ?></p>
            <dl class="row small mb-0">
                <dt class="col-5">Username</dt><dd class="col-7"><?= e($customer['username']) ?></dd>
                <dt class="col-5">Email</dt><dd class="col-7"><?= e($customer['email']) ?></dd>
                <dt class="col-5">Phone</dt><dd class="col-7"><?= e($customer['phone'] ?? '—') ?></dd>
                <dt class="col-5">National ID</dt><dd class="col-7"><?= e($customer['national_id']) ?></dd>
                <dt class="col-5">Date of Birth</dt><dd class="col-7"><?= display_date($customer['date_of_birth']) ?></dd>
                <dt class="col-5">Gender</dt><dd class="col-7"><?= e(ucfirst($customer['gender'])) ?></dd>
                <dt class="col-5">Address</dt><dd class="col-7"><?= e($customer['address_line']) ?>, <?= e($customer['city']) ?></dd>
                <dt class="col-5">Occupation</dt><dd class="col-7"><?= e($customer['occupation'] ?? '—') ?></dd>
                <dt class="col-5">Monthly Income</dt><dd class="col-7"><?= $customer['monthly_income'] !== null ? money($customer['monthly_income']) : '—' ?></dd>
            </dl>
            <a href="<?= e(BASE_URL) ?>/officer/customers/edit.php?id=<?= $customerId ?>" class="btn btn-brass w-100 mt-3"><i class="bi bi-pencil me-1"></i> Edit Profile</a>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="mfs-card p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Loan Applications</h2>
                <a href="<?= e(BASE_URL) ?>/officer/applications/create.php?customer_id=<?= $customerId ?>" class="btn btn-sm btn-brass">New Application</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mfs-table align-middle">
                    <thead><tr><th>Code</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($apps as $a): ?>
                        <tr>
                            <td><?= e($a['application_code']) ?></td>
                            <td><?= e($a['type_name']) ?></td>
                            <td><?= money($a['requested_amount']) ?></td>
                            <td><?= status_badge($a['status']) ?></td>
                            <td><?= display_date($a['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($apps)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No applications yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mfs-card p-3">
            <h2 class="h6 mb-3">Loans</h2>
            <div class="table-responsive">
                <table class="table table-sm mfs-table align-middle">
                    <thead><tr><th>Loan</th><th>Type</th><th>Principal</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($loans as $l): ?>
                        <tr>
                            <td><?= e($l['loan_code']) ?></td>
                            <td><?= e($l['type_name']) ?></td>
                            <td><?= money($l['principal_amount']) ?></td>
                            <td><?= status_badge($l['status']) ?></td>
                            <td><a href="<?= e(BASE_URL) ?>/officer/loans/view.php?id=<?= (int) $l['loan_id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($loans)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No loans yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
