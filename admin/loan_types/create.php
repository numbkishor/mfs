<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$errors = [];
$old = [
    'type_name' => '', 'description' => '', 'interest_rate' => '', 'min_amount' => '', 'max_amount' => '',
    'min_duration_months' => '', 'max_duration_months' => '', 'processing_fee_pct' => '0',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $key => $default) {
        $old[$key] = clean($_POST[$key] ?? $default);
    }

    if ($old['type_name'] === '') $errors[] = 'Type name is required.';
    foreach (['interest_rate', 'min_amount', 'max_amount', 'processing_fee_pct'] as $numField) {
        if (!is_numeric($old[$numField])) $errors[] = ucwords(str_replace('_', ' ', $numField)) . ' must be a number.';
    }
    foreach (['min_duration_months', 'max_duration_months'] as $intField) {
        if (!ctype_digit($old[$intField])) $errors[] = ucwords(str_replace('_', ' ', $intField)) . ' must be a whole number.';
    }
    if (empty($errors) && (float) $old['max_amount'] < (float) $old['min_amount']) {
        $errors[] = 'Maximum amount must be greater than or equal to minimum amount.';
    }
    if (empty($errors) && (int) $old['max_duration_months'] < (int) $old['min_duration_months']) {
        $errors[] = 'Maximum duration must be greater than or equal to minimum duration.';
    }

    if (empty($errors)) {
        $pdo = Database::getConnection();
        $dup = $pdo->prepare('SELECT COUNT(*) FROM loan_types WHERE type_name = :n');
        $dup->execute(['n' => $old['type_name']]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors[] = 'A loan type with that name already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO loan_types (type_name, description, interest_rate, min_amount, max_amount, min_duration_months, max_duration_months, processing_fee_pct, status)
             VALUES (:name, :desc, :rate, :min_amt, :max_amt, :min_dur, :max_dur, :fee, "active")'
        );
        $stmt->execute([
            'name' => $old['type_name'], 'desc' => $old['description'] ?: null, 'rate' => $old['interest_rate'],
            'min_amt' => $old['min_amount'], 'max_amt' => $old['max_amount'],
            'min_dur' => $old['min_duration_months'], 'max_dur' => $old['max_duration_months'], 'fee' => $old['processing_fee_pct'],
        ]);
        flash('success', 'Loan type created.');
        redirect('/admin/loan_types/index.php');
    }
}

$pageTitle = 'New Loan Type';
$activeMenu = 'loan_types';
$breadcrumb = ['Loan Types' => '/admin/loan_types/index.php', 'New' => null];
require_once __DIR__ . '/../../includes/header.php';
require __DIR__ . '/_form.php';
require_once __DIR__ . '/../../includes/footer.php';
