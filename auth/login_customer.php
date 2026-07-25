<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(role_home_path((int) $_SESSION['role_id']));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = clean($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter both username and password.';
    } else {
        $user = attempt_customer_login($username, $password);

        if ($user) {
            establish_session($user);
            flash('success', 'Welcome back, ' . $user['full_name'] . '.');
            redirect('/customer/dashboard.php');
        }

        $errors[] = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login · <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,500;8..60,600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(BASE_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="mfs-auth-shell">
    <div class="mfs-auth-side">
        <div>
            <div class="brand-mark">M</div>
            <h2 class="mt-4" style="color:#f4ede0;">Your loan,<br>always in view.</h2>
            <p style="color:#c7d3da; max-width: 380px;">Check your repayment schedule, outstanding balance and full payment history any time.</p>
        </div>
        <div class="ledger-lines">
            <span></span><span></span><span></span><span></span>
            <p style="color:#8a97a0; font-size:.8rem; margin-top:1rem;">Microfinance Loan Management System &middot; Customer Portal</p>
        </div>
    </div>
    <div class="mfs-auth-form-col">
        <div class="mfs-auth-card">
            <div class="mfs-auth-tabs">
                <a href="<?= e(BASE_URL) ?>/auth/login_employee.php">Employee</a>
                <a href="<?= e(BASE_URL) ?>/auth/login_customer.php" class="active">Customer</a>
            </div>
            <h3 class="h4 mb-1">Customer sign in</h3>
            <p class="text-muted small mb-4">Accounts are created for you by your loan officer &mdash; there is no self sign-up.</p>

            <?php render_flash(); ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger py-2"><?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="post" class="needs-validation" novalidate>
                <?php csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
                    <div class="invalid-feedback">Username is required.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback">Password is required.</div>
                </div>
                <button type="submit" class="btn btn-brass w-100 py-2">Sign in</button>
            </form>
            <p class="text-muted small mt-4 mb-0">Sample customer: <code>fatema.begum</code> / <code>Customer@123</code></p>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/app.js"></script>
</body>
</html>
