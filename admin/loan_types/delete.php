<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::getConnection();

$inUse = $pdo->prepare('SELECT COUNT(*) FROM loan_applications WHERE loan_type_id = :id AND status = :status');
$inUse->execute(['id' => $id, 'status' => 'pending']);

if ((int) $inUse->fetchColumn() > 0) {
    flash('danger', 'This loan type has pending applications and cannot be deactivated.');
    redirect('/admin/loan_types/index.php');
}

$stmt = $pdo->prepare("UPDATE loan_types SET status = 'inactive' WHERE loan_type_id = :id");
$stmt->execute(['id' => $id]);

flash('success', 'Loan type deactivated.');
redirect('/admin/loan_types/index.php');
