<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

/**
 * Generate (or reuse) the CSRF token for the current session and return it.
 */
function csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }

    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Echo a hidden <input> field carrying the current CSRF token.
 * Call inside every <form> that performs a state-changing action.
 */
function csrf_field(): void
{
    echo '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate a submitted token against the session token.
 * Halts the request with a 400 response on mismatch.
 */
function csrf_verify(): void
{
    $submitted = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (empty($_SESSION[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], (string) $submitted)) {
        http_response_code(400);
        die('Invalid or expired security token. Please go back, refresh the page, and try again.');
    }
}
