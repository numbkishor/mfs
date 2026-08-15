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

$portal = 'customer';
$pageTitle = 'Customer Login';
$heading = 'Sign in';
$subheading = 'Accounts are created by your loan officer — there is no self sign-up.';
$submitClass = 'btn-brass';
$showRemember = false;
$hint = 'Sample customer: <code>fatema.begum</code> / <code>Customer@123</code>';

require __DIR__ . '/../includes/auth_view.php';
