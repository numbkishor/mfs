<?php

declare(strict_types=1);

/**
 * Global application configuration.
 * Adjust BASE_URL to match your XAMPP htdocs folder name.
 */

// ---------------------------------------------------------------------
// Environment
// ---------------------------------------------------------------------
define('APP_ENV', 'development'); // development | production
define('APP_NAME', 'Microfinance Loan Management System');
define('APP_VERSION', '1.0.0');

// Change this if the project folder inside htdocs has a different name.
define('BASE_URL', '/mfs');

// ---------------------------------------------------------------------
// Filesystem paths
// ---------------------------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');

// ---------------------------------------------------------------------
// Database credentials (default XAMPP values)
// ---------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'microfinance_lms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------
// Security
// ---------------------------------------------------------------------
define('SESSION_NAME', 'mfs_session');
define('SESSION_LIFETIME', 7200);        // 2 hours in seconds
define('REMEMBER_ME_LIFETIME', 60 * 60 * 24 * 30); // 30 days
define('CSRF_TOKEN_NAME', 'csrf_token');
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 300);    // 5 minutes

// ---------------------------------------------------------------------
// Role IDs (must match the roles table seed data)
// ---------------------------------------------------------------------
define('ROLE_ADMIN', 1);
define('ROLE_MANAGER', 2);
define('ROLE_OFFICER', 3);
define('ROLE_CUSTOMER', 4);
define('ROLE_CASHIER', 5);

// ---------------------------------------------------------------------
// Error reporting
// ---------------------------------------------------------------------
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php_errors.log');

date_default_timezone_set('Asia/Dhaka');
