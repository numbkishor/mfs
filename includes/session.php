<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Absolute idle timeout: if the session has been idle beyond the
// configured lifetime, force a logout.
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
    $_SESSION = [];
    session_destroy();
    session_start();
    // Restarting reuses the old session id, which leaves the expired id
    // valid and usable for fixation. Issue a fresh one.
    session_regenerate_id(true);
    $_SESSION = [];
}
$_SESSION['last_activity'] = time();

// Periodically regenerate the session id to mitigate session fixation.
if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
} elseif (time() - $_SESSION['created_at'] > 900) {
    session_regenerate_id(true);
    $_SESSION['created_at'] = time();
}
