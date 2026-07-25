<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Escape a string for safe HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Trim and strip a value coming from user input. Does NOT escape for
 * HTML output — use e() at render time instead so data stored/compared
 * stays clean.
 */
function clean(?string $value): string
{
    return trim($value ?? '');
}

/**
 * Redirect to an internal path (relative to BASE_URL) and stop execution.
 */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/**
 * Queue a one-time flash message rendered by includes/alerts.php.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Pop and render any queued flash messages as Bootstrap alerts.
 */
function render_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    foreach ($_SESSION['flash'] as $item) {
        $type = e($item['type']);
        $message = e($item['message']);
        echo "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">"
            . $message
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            . '</div>';
    }

    unset($_SESSION['flash']);
}

/**
 * Format a numeric amount as currency (BDT Taka by default).
 */
function money(float|string $amount, string $symbol = '৳'): string
{
    return $symbol . ' ' . number_format((float) $amount, 2);
}

/**
 * Format a date string for display.
 */
function display_date(?string $date, string $format = 'd M Y'): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }

    $ts = strtotime($date);

    return $ts === false ? '—' : date($format, $ts);
}

/**
 * Generate a unique, human-friendly sequential code such as
 * CUS-0004, APP-000123, LN-000045, PAY-000980.
 *
 * @param string $table  table to inspect
 * @param string $column column holding the code
 * @param string $prefix code prefix
 * @param int    $pad    zero-padding width for the numeric part
 */
function generate_code(string $table, string $column, string $prefix, int $pad = 4): string
{
    $pdo = Database::getConnection();

    $allowedTables = [
        'customers', 'loan_applications', 'loans', 'payments',
    ];
    $allowedColumns = [
        'customer_code', 'application_code', 'loan_code', 'payment_code',
    ];

    if (!in_array($table, $allowedTables, true) || !in_array($column, $allowedColumns, true)) {
        throw new InvalidArgumentException('Unsupported table/column for code generation.');
    }

    $stmt = $pdo->query("SELECT {$column} FROM {$table} ORDER BY " . str_replace('_code', '_id', $column) . " DESC LIMIT 1");
    $last = $stmt->fetchColumn();

    $nextNumber = 1;
    if ($last !== false && preg_match('/(\d+)$/', (string) $last, $matches)) {
        $nextNumber = (int) $matches[1] + 1;
    }

    return $prefix . '-' . str_pad((string) $nextNumber, $pad, '0', STR_PAD_LEFT);
}

/**
 * Write a line to the application log file under /logs.
 */
function app_log(string $message, string $level = 'INFO'): void
{
    $line = sprintf('[%s] [%s] %s%s', date('Y-m-d H:i:s'), $level, $message, PHP_EOL);
    @file_put_contents(LOG_PATH . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

/**
 * Render a Bootstrap badge colour-coded by a status string
 * (active, pending, approved, rejected, overdue, paid, ...).
 */
function status_badge(string $status): string
{
    $slug = strtolower(str_replace(' ', '-', $status));
    return '<span class="badge badge-status-' . e($slug) . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

/**
 * Search + paginate the loan applications list, optionally scoped to a
 * specific submitting officer (submittedBy) and/or a status filter.
 *
 * @return array{rows: array<int, array<string, mixed>>, total: int}
 */
function fetch_applications_list(string $status, ?int $submittedBy, int $limit, int $offset): array
{
    $pdo = Database::getConnection();

    $where = ['1=1'];
    $params = [];

    if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected', 'returned'], true)) {
        $where[] = 'a.status = :status';
        $params['status'] = $status;
    }

    if ($submittedBy !== null) {
        $where[] = 'a.submitted_by = :submitted_by';
        $params['submitted_by'] = $submittedBy;
    }

    $whereSql = implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM loan_applications a WHERE {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT a.application_id, a.application_code, a.requested_amount, a.requested_duration_months,
                a.status, a.created_at, uc.full_name AS customer_name, c.customer_code, lt.type_name,
                uo.full_name AS officer_name
         FROM loan_applications a
         INNER JOIN customers c ON c.customer_id = a.customer_id
         INNER JOIN users uc ON uc.user_id = c.user_id
         INNER JOIN loan_types lt ON lt.loan_type_id = a.loan_type_id
         INNER JOIN users uo ON uo.user_id = a.submitted_by
         WHERE {$whereSql}
         ORDER BY a.created_at DESC
         LIMIT :limit OFFSET :offset"
    );

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

/**
 * Search + paginate the loans list, optionally scoped to loans whose
 * application was submitted by a specific officer.
 *
 * @return array{rows: array<int, array<string, mixed>>, total: int}
 */
function fetch_loans_list(string $status, ?int $submittedBy, int $limit, int $offset): array
{
    $pdo = Database::getConnection();

    $where = ['1=1'];
    $params = [];

    if ($status !== '' && in_array($status, ['active', 'closed', 'defaulted', 'cancelled'], true)) {
        $where[] = 'l.status = :status';
        $params['status'] = $status;
    }

    if ($submittedBy !== null) {
        $where[] = 'a.submitted_by = :submitted_by';
        $params['submitted_by'] = $submittedBy;
    }

    $whereSql = implode(' AND ', $where);

    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM loans l INNER JOIN loan_applications a ON a.application_id = l.application_id WHERE {$whereSql}"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT l.loan_id, l.loan_code, l.principal_amount, l.status, l.start_date, l.end_date,
                uc.full_name AS customer_name, c.customer_code, lt.type_name
         FROM loans l
         INNER JOIN loan_applications a ON a.application_id = l.application_id
         INNER JOIN customers c ON c.customer_id = l.customer_id
         INNER JOIN users uc ON uc.user_id = c.user_id
         INNER JOIN loan_types lt ON lt.loan_type_id = l.loan_type_id
         WHERE {$whereSql}
         ORDER BY l.created_at DESC
         LIMIT :limit OFFSET :offset"
    );

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

/**
 * Simple pagination calculator.
 *
 * @return array{page:int, limit:int, offset:int}
 */
function paginate(int $totalRows, int $limit = 10): array
{
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $totalPages = (int) max(1, ceil($totalRows / $limit));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;

    return ['page' => $page, 'limit' => $limit, 'offset' => $offset, 'total_pages' => $totalPages];
}

/**
 * Search + paginate the customers list. Shared by the admin, manager and
 * officer "Customers" modules so the query logic lives in one place.
 *
 * @return array{rows: array<int, array<string, mixed>>, total: int}
 */
function fetch_customers_list(string $search, string $status, int $limit, int $offset): array
{
    $pdo = Database::getConnection();

    $where = ['1=1'];
    $params = [];

    if ($search !== '') {
        $where[] = '(u.full_name LIKE :search OR c.customer_code LIKE :search OR c.national_id LIKE :search OR u.phone LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($status !== '' && in_array($status, ['active', 'inactive', 'blacklisted'], true)) {
        $where[] = 'c.status = :status';
        $params['status'] = $status;
    }

    $whereSql = implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers c INNER JOIN users u ON u.user_id = c.user_id WHERE {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT c.customer_id, c.customer_code, c.national_id, c.city, c.status, c.monthly_income,
                u.full_name, u.email, u.phone, u.username
         FROM customers c
         INNER JOIN users u ON u.user_id = c.user_id
         WHERE {$whereSql}
         ORDER BY c.created_at DESC
         LIMIT :limit OFFSET :offset"
    );

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

/**
 * Render Bootstrap pagination links, preserving existing query params.
 */
function render_pagination(int $currentPage, int $totalPages): void
{
    if ($totalPages <= 1) {
        return;
    }

    $params = $_GET;
    echo '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

    for ($i = 1; $i <= $totalPages; $i++) {
        $params['page'] = $i;
        $url = '?' . http_build_query($params);
        $active = $i === $currentPage ? ' active' : '';
        echo "<li class=\"page-item{$active}\"><a class=\"page-link\" href=\"" . e($url) . "\">{$i}</a></li>";
    }

    echo '</ul></nav>';
}
