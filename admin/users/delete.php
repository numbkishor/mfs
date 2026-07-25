<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$id = (int) ($_GET['id'] ?? 0);
$admin = current_user();

if ($id === $admin['user_id']) {
    flash('danger', 'You cannot suspend your own account.');
    redirect('/admin/users/index.php');
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE user_id = :id AND role_id IN (1,2,3,5)");
$stmt->execute(['id' => $id]);

app_log("Admin {$admin['username']} suspended employee #{$id}.");
flash('success', 'Employee account suspended.');
redirect('/admin/users/index.php');
