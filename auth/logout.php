<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$wasCustomer = isset($_SESSION['role_id']) && (int) $_SESSION['role_id'] === ROLE_CUSTOMER;

logout();

session_start();
flash('info', 'You have been logged out.');

redirect($wasCustomer ? '/auth/login_customer.php' : '/auth/login_employee.php');
