<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$months = (int) ($_GET['months'] ?? 12);
$months = max(3, min(24, $months));
$monthly = get_monthly_cash_flow($months);

$pdo = Database::getConnection();
$newLoansStmt = $pdo->prepare(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
     FROM loans WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :m MONTH) GROUP BY ym"
);
$newLoansStmt->execute(['m' => $months]);
$newLoansByMonth = [];
foreach ($newLoansStmt->fetchAll() as $r) {
    $newLoansByMonth[$r['ym']] = (int) $r['cnt'];
}

foreach ($monthly as &$row) {
    $row['new_loans'] = 0;
}
unset($row);

$i = 0;
foreach ($monthly as &$row) {
    $tsMonth = strtotime('-' . ($months - 1 - $i) . ' months');
    $ymKey = date('Y-m', $tsMonth);
    $row['new_loans'] = $newLoansByMonth[$ymKey] ?? 0;
    $i++;
}
unset($row);

$pageTitle = 'Monthly Financial Report';
$activeMenu = 'reports';
$breadcrumb = ['Reports' => '/admin/reports/index.php', 'Monthly' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between mb-3 d-print-none">
    <form method="get" class="d-flex gap-2">
        <select name="months" class="form-select" onchange="this.form.submit()">
            <?php foreach ([3, 6, 12, 24] as $m): ?>
                <option value="<?= $m ?>" <?= $months === $m ? 'selected' : '' ?>>Last <?= $m ?> months</option>
            <?php endforeach; ?>
        </select>
    </form>
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="mfs-card p-3">
    <div class="table-responsive">
        <table class="table mfs-table align-middle">
            <thead><tr><th>Month</th><th>New Loans</th><th>Disbursed</th><th>Repaid</th><th>Net Cash Movement</th></tr></thead>
            <tbody>
            <?php foreach ($monthly as $m): ?>
                <tr>
                    <td><?= e($m['month']) ?></td>
                    <td><?= (int) $m['new_loans'] ?></td>
                    <td><?= money($m['disbursed']) ?></td>
                    <td><?= money($m['repaid']) ?></td>
                    <td><?= money($m['repaid'] - $m['disbursed']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
