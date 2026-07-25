<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(role_home_path((int) $_SESSION['role_id']));
}

redirect('/auth/login_employee.php');
