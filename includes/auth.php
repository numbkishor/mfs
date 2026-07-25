<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Authentication / Authorization middleware.
 *
 * Roles are ALWAYS re-read from the users/roles tables at login time and
 * stored in the session as role_id + role_name. Nothing about the role
 * is ever accepted from client input.
 */

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role_id']);
}

/**
 * Return the currently authenticated user's session data, or null.
 *
 * @return array{user_id:int, role_id:int, role_name:string, full_name:string, username:string}|null
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'user_id'     => (int) $_SESSION['user_id'],
        'role_id'     => (int) $_SESSION['role_id'],
        'role_name'   => (string) $_SESSION['role_name'],
        'full_name'   => (string) $_SESSION['full_name'],
        'username'    => (string) $_SESSION['username'],
        'customer_id' => isset($_SESSION['customer_id']) ? (int) $_SESSION['customer_id'] : null,
    ];
}

/**
 * Force the visitor to be logged in. Employees and customers are sent to
 * different login pages depending on which area they attempted to reach.
 */
function require_login(string $portal = 'employee'): void
{
    if (!is_logged_in()) {
        $target = $portal === 'customer' ? '/auth/login_customer.php' : '/auth/login_employee.php';
        redirect($target);
    }
}

/**
 * Restrict the current route to one or more role IDs. Call require_login()
 * first (each dashboard entry point does this via its bootstrap include).
 */
function require_role(int ...$roleIds): void
{
    $user = current_user();

    if ($user === null || !in_array($user['role_id'], $roleIds, true)) {
        http_response_code(403);
        flash('danger', 'You do not have permission to access that page.');
        redirect(role_home_path($user['role_id'] ?? 0));
    }
}

/**
 * Map a role id to its dashboard's home path.
 */
function role_home_path(int $roleId): string
{
    return match ($roleId) {
        ROLE_ADMIN    => '/admin/dashboard.php',
        ROLE_MANAGER  => '/manager/dashboard.php',
        ROLE_OFFICER  => '/officer/dashboard.php',
        ROLE_CASHIER  => '/cashier/dashboard.php',
        ROLE_CUSTOMER => '/customer/dashboard.php',
        default       => '/auth/login_employee.php',
    };
}

/**
 * Attempt to authenticate an employee (Administrator / Manager / Loan
 * Officer) by username + password. Returns the user row on success or
 * null on failure. Applies simple rate limiting against brute force.
 */
function attempt_employee_login(string $username, string $password): ?array
{
    return attempt_login($username, $password, [ROLE_ADMIN, ROLE_MANAGER, ROLE_OFFICER, ROLE_CASHIER]);
}

/**
 * Attempt to authenticate a customer by username + password.
 */
function attempt_customer_login(string $username, string $password): ?array
{
    return attempt_login($username, $password, [ROLE_CUSTOMER]);
}

function attempt_login(string $username, string $password, array $allowedRoleIds): ?array
{
    if (is_locked_out($username)) {
        flash('danger', 'Too many failed attempts. Please try again in a few minutes.');
        return null;
    }

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare(
        'SELECT u.user_id, u.role_id, r.role_name, u.full_name, u.username, u.password_hash, u.status
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE u.username = :username
         LIMIT 1'
    );
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !in_array((int) $user['role_id'], $allowedRoleIds, true)) {
        register_failed_attempt($username);
        return null;
    }

    if ($user['status'] !== 'active') {
        flash('danger', 'This account is not active. Please contact your administrator.');
        return null;
    }

    if (!password_verify($password, $user['password_hash'])) {
        register_failed_attempt($username);
        return null;
    }

    clear_failed_attempts($username);

    // Transparent rehash if PHP's default cost factor changes in the future.
    if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT)) {
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :id');
        $update->execute(['hash' => $newHash, 'id' => $user['user_id']]);
    }

    return $user;
}

/**
 * Finalize a successful login: regenerate the session id, store role
 * data, update last_login_at, and (for customers) attach the linked
 * customer_id.
 */
function establish_session(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['user_id'];
    $_SESSION['role_id']    = (int) $user['role_id'];
    $_SESSION['role_name']  = $user['role_name'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['created_at'] = time();

    $pdo = Database::getConnection();

    if ((int) $user['role_id'] === ROLE_CUSTOMER) {
        $stmt = $pdo->prepare('SELECT customer_id FROM customers WHERE user_id = :uid LIMIT 1');
        $stmt->execute(['uid' => $user['user_id']]);
        $customerId = $stmt->fetchColumn();
        $_SESSION['customer_id'] = $customerId !== false ? (int) $customerId : null;
    }

    $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE user_id = :id');
    $update->execute(['id' => $user['user_id']]);

    app_log("User '{$user['username']}' (role {$user['role_id']}) logged in.");
}

function logout(): void
{
    $username = $_SESSION['username'] ?? 'unknown';
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
    app_log("User '{$username}' logged out.");
}

// -----------------------------------------------------------------
// Very small in-session rate limiter for login attempts. Sufficient
// for a coursework-scale deployment without adding extra tables.
// -----------------------------------------------------------------

function register_failed_attempt(string $username): void
{
    $key = 'login_attempts_' . md5($username);
    $_SESSION[$key] = $_SESSION[$key] ?? ['count' => 0, 'first' => time()];
    $_SESSION[$key]['count']++;
    $_SESSION[$key]['last'] = time();
}

function clear_failed_attempts(string $username): void
{
    unset($_SESSION['login_attempts_' . md5($username)]);
}

function is_locked_out(string $username): bool
{
    $key = 'login_attempts_' . md5($username);
    $data = $_SESSION[$key] ?? null;

    if (!$data) {
        return false;
    }

    if ($data['count'] >= LOGIN_MAX_ATTEMPTS) {
        if ((time() - $data['last']) < LOGIN_LOCKOUT_SECONDS) {
            return true;
        }
        clear_failed_attempts($username);
    }

    return false;
}
