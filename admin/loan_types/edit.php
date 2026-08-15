<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::getConnection();

$stmt = $pdo->prepare('SELECT * FROM loan_types WHERE loan_type_id = :id');
$stmt->execute(['id' => $id]);
$loanType = $stmt->fetch();

if (!$loanType) {
    flash('danger', 'Loan type not found.');
    redirect('/admin/loan_types/index.php');
}

$errors = [];
$old = $loanType;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (['type_name', 'description', 'interest_rate', 'min_amount', 'max_amount', 'min_duration_months', 'max_duration_months', 'processing_fee_pct', 'status'] as $field) {
        $old[$field] = clean($_POST[$field] ?? ($old[$field] ?? ''));
    }

    if ($old['type_name'] === '') $errors[] = 'Type name is required.';
    foreach (['interest_rate', 'min_amount', 'max_amount', 'processing_fee_pct'] as $numField) {
        if (!is_numeric($old[$numField])) $errors[] = ucwords(str_replace('_', ' ', $numField)) . ' must be a number.';
    }
    foreach (['min_duration_months', 'max_duration_months'] as $intField) {
        if (!ctype_digit((string) $old[$intField])) $errors[] = ucwords(str_replace('_', ' ', $intField)) . ' must be a whole number.';
    }
    if (empty($errors) && (float) $old['max_amount'] < (float) $old['min_amount']) {
        $errors[] = 'Maximum amount must be greater than or equal to minimum amount.';
    }
    if (empty($errors) && (int) $old['max_duration_months'] < (int) $old['min_duration_months']) {
        $errors[] = 'Maximum duration must be greater than or equal to minimum duration.';
    }
    if (!in_array($old['status'], ['active', 'inactive'], true)) {
        $errors[] = 'Select a valid status.';
    }

    if (empty($errors)) {
        $update = $pdo->prepare(
            'UPDATE loan_types SET type_name=:name, description=:desc, interest_rate=:rate, min_amount=:min_amt,
             max_amount=:max_amt, min_duration_months=:min_dur, max_duration_months=:max_dur,
             processing_fee_pct=:fee, status=:status WHERE loan_type_id=:id'
        );
        $update->execute([
            'name' => $old['type_name'], 'desc' => $old['description'] ?: null, 'rate' => $old['interest_rate'],
            'min_amt' => $old['min_amount'], 'max_amt' => $old['max_amount'],
            'min_dur' => $old['min_duration_months'], 'max_dur' => $old['max_duration_months'],
            'fee' => $old['processing_fee_pct'], 'status' => $old['status'], 'id' => $id,
        ]);
        flash('success', 'Loan type updated.');
        redirect('/admin/loan_types/index.php');
    }
}

$pageTitle = 'Edit Loan Type';
$activeMenu = 'loan_types';
$breadcrumb = ['Loan Types' => '/admin/loan_types/index.php', $loanType['type_name'] => null];
require_once __DIR__ . '/../../includes/header.php';
$showStatus  = true;
$submitLabel = 'Save Changes';
require __DIR__ . '/_form.php';
require_once __DIR__ . '/../../includes/footer.php';
