<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_OFFICER);

$customerId = (int) ($_GET['id'] ?? 0);
$pdo = Database::getConnection();

// Customers with active loans cannot be deactivated — preserves
// referential/business integrity without ever hard-deleting financial history.
$activeLoans = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE customer_id = :id AND status = 'active'");
$activeLoans->execute(['id' => $customerId]);

if ((int) $activeLoans->fetchColumn() > 0) {
    flash('danger', 'This customer has active loans and cannot be deactivated.');
    redirect('/officer/customers/view.php?id=' . $customerId);
}

$stmt = $pdo->prepare("UPDATE customers SET status = 'inactive' WHERE customer_id = :id");
$stmt->execute(['id' => $customerId]);

$userStmt = $pdo->prepare(
    "UPDATE users u INNER JOIN customers c ON c.user_id = u.user_id
     SET u.status = 'inactive' WHERE c.customer_id = :id"
);
$userStmt->execute(['id' => $customerId]);

app_log("Customer #{$customerId} deactivated by " . current_user()['username']);
flash('success', 'Customer has been deactivated.');
redirect('/officer/customers/index.php');
