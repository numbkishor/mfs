<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$pageTitle = 'Reports Centre';
$activeMenu = 'reports';
$breadcrumb = ['Reports' => null];
require_once __DIR__ . '/../../includes/header.php';

$reports = [
    ['title' => 'Cash Flow Report', 'desc' => 'Available cash, disbursements, repayments and interest collected — always computed live.', 'icon' => 'bi-graph-up-arrow', 'url' => '/admin/reports/cashflow.php'],
    ['title' => 'Customer Report', 'desc' => 'All customers with total borrowed and current outstanding balance.', 'icon' => 'bi-people', 'url' => '/admin/reports/customers.php'],
    ['title' => 'Loan Report', 'desc' => 'Every loan with status, principal, and outstanding balance.', 'icon' => 'bi-cash-coin', 'url' => '/admin/reports/loans.php'],
    ['title' => 'Collection / Repayment Report', 'desc' => 'Payments collected within a date range, grouped by officer.', 'icon' => 'bi-receipt', 'url' => '/admin/reports/repayments.php'],
    ['title' => 'Overdue Report', 'desc' => 'Installments past their due date and not fully paid.', 'icon' => 'bi-exclamation-triangle', 'url' => '/admin/reports/overdue.php'],
    ['title' => 'Monthly Financial Report', 'desc' => 'Month-by-month disbursement and collection totals.', 'icon' => 'bi-calendar3', 'url' => '/admin/reports/monthly.php'],
];
?>

<div class="row g-3">
    <?php foreach ($reports as $r): ?>
        <div class="col-md-6 col-lg-4">
            <a href="<?= e(BASE_URL . $r['url']) ?>" class="text-decoration-none">
                <div class="mfs-card p-3 h-100">
                    <i class="bi <?= e($r['icon']) ?> fs-3 text-secondary"></i>
                    <h2 class="h6 mt-2 mb-1"><?= e($r['title']) ?></h2>
                    <p class="text-muted small mb-0"><?= e($r['desc']) ?></p>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
