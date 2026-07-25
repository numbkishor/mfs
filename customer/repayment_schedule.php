<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login('customer');
require_role(ROLE_CUSTOMER);
refresh_overdue_statuses();

$user = current_user();
$pdo = Database::getConnection();

$loansStmt = $pdo->prepare('SELECT loan_id, loan_code FROM loans WHERE customer_id = ? ORDER BY created_at DESC');
$loansStmt->execute([$user['customer_id']]);
$loans = $loansStmt->fetchAll();

$loanId = (int) ($_GET['loan_id'] ?? ($loans[0]['loan_id'] ?? 0));

// Security: make sure the requested loan actually belongs to this customer.
$ownsLoan = false;
foreach ($loans as $l) {
    if ((int) $l['loan_id'] === $loanId) {
        $ownsLoan = true;
        break;
    }
}

$schedule = [];
if ($ownsLoan && $loanId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM repayment_schedules WHERE loan_id = :id ORDER BY installment_no ASC');
    $stmt->execute(['id' => $loanId]);
    $schedule = $stmt->fetchAll();
}

$pageTitle = 'Repayment Schedule';
$activeMenu = 'schedule';
$breadcrumb = ['Repayment Schedule' => null];
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (empty($loans)): ?>
    <div class="mfs-card p-3">
        <p class="text-muted mb-0">You have no loans yet, so there is no repayment schedule to show.</p>
    </div>
<?php else: ?>
    <form method="get" class="mb-3">
        <select name="loan_id" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
            <?php foreach ($loans as $l): ?>
                <option value="<?= (int) $l['loan_id'] ?>" <?= $loanId === (int) $l['loan_id'] ? 'selected' : '' ?>><?= e($l['loan_code']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="mfs-card p-3">
        <div class="table-responsive">
            <table class="table mfs-table align-middle">
                <thead><tr><th>#</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total Due</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($schedule as $line): ?>
                    <tr>
                        <td><?= (int) $line['installment_no'] ?></td>
                        <td><?= display_date($line['due_date']) ?></td>
                        <td><?= money($line['principal_due']) ?></td>
                        <td><?= money($line['interest_due']) ?></td>
                        <td><?= money($line['total_due']) ?></td>
                        <td><?= money($line['amount_paid']) ?></td>
                        <td><?= money($line['total_due'] - $line['amount_paid']) ?></td>
                        <td><?= status_badge($line['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
