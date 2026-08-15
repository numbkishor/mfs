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
    $remember = isset($_POST['remember']);

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter both username and password.';
    } else {
        $user = attempt_employee_login($username, $password);

        if ($user) {
            establish_session($user);

            if ($remember) {
                remember_user((int) $user['user_id']);
            }

            flash('success', 'Welcome back, ' . $user['full_name'] . '.');
            redirect(role_home_path((int) $user['role_id']));
        }

        $errors[] = 'Invalid username or password.';
    }
}

$portal = 'employee';
$pageTitle = 'Employee Login';
$heading = 'Sign in';
$subheading = 'Administrator, Manager, Loan Officer and Cashier accounts.';
$submitClass = 'btn-navy';
$showRemember = true;
$hint = 'Default administrator: <code>admin</code> / <code>Admin@123</code>';

require __DIR__ . '/../includes/auth_view.php';
