<?php

declare(strict_types=1);

/** Builds the sidebar menu for the current user's role. */

$roleId = (int) $user['role_id'];

// Guarded so a double include can never fatal on a redeclare.
if (!function_exists('nav_link')) {
    function nav_link(string $key, string $active, string $url, string $icon, string $label): void
    {
        $isActive = $key === $active ? ' active' : '';
        $current = $isActive ? ' aria-current="page"' : '';
        echo '<a class="nav-link' . $isActive . '" href="' . e(BASE_URL . $url) . '" title="' . e($label) . '"' . $current . '>'
            . '<i class="bi ' . e($icon) . '" aria-hidden="true"></i>'
            . '<span>' . e($label) . '</span>'
            . '</a>';
    }
}

$menus = [
    ROLE_ADMIN => [
        ['dashboard', '/admin/dashboard.php', 'bi-speedometer2', 'Dashboard'],
        ['section', null, null, 'Loan Operations'],
        ['customers', '/admin/customers/index.php', 'bi-people', 'Customers'],
        ['loan_types', '/admin/loan_types/index.php', 'bi-tags', 'Loan Types'],
        ['applications', '/admin/applications/index.php', 'bi-file-earmark-text', 'Applications'],
        ['loans', '/admin/loans/index.php', 'bi-cash-coin', 'Loans'],
        ['repayments', '/admin/repayments/index.php', 'bi-calendar-check', 'Repayments'],
        ['section', null, null, 'Insights'],
        ['cashflow', '/admin/reports/cashflow.php', 'bi-graph-up-arrow', 'Cash Flow'],
        ['reports', '/admin/reports/index.php', 'bi-bar-chart-line', 'Reports'],
        ['section', null, null, 'Administration'],
        ['users', '/admin/users/index.php', 'bi-person-badge', 'Employee Accounts'],
    ],
    ROLE_MANAGER => [
        ['dashboard', '/manager/dashboard.php', 'bi-speedometer2', 'Dashboard'],
        ['section', null, null, 'Loan Operations'],
        ['customers', '/manager/customers/index.php', 'bi-people', 'Customers'],
        ['loan_types', '/manager/loan_types/index.php', 'bi-tags', 'Loan Types'],
        ['applications', '/manager/applications/index.php', 'bi-file-earmark-text', 'Applications'],
        ['loans', '/manager/loans/index.php', 'bi-cash-coin', 'Loans'],
        ['repayments', '/manager/repayments/index.php', 'bi-calendar-check', 'Repayments'],
        ['section', null, null, 'Insights'],
        ['cashflow', '/manager/reports/cashflow.php', 'bi-graph-up-arrow', 'Cash Flow'],
        ['reports', '/manager/reports/index.php', 'bi-bar-chart-line', 'Reports'],
    ],
    ROLE_OFFICER => [
        ['dashboard', '/officer/dashboard.php', 'bi-speedometer2', 'Dashboard'],
        ['section', null, null, 'My Work'],
        ['customers', '/officer/customers/index.php', 'bi-people', 'Customers'],
        ['applications', '/officer/applications/index.php', 'bi-file-earmark-text', 'Applications'],
        ['loans', '/officer/loans/index.php', 'bi-cash-coin', 'Loans'],
        ['repayments', '/officer/repayments/index.php', 'bi-calendar-check', 'Repayments'],
    ],
    ROLE_CASHIER => [
        ['dashboard', '/cashier/dashboard.php', 'bi-speedometer2', 'Dashboard'],
        ['section', null, null, 'Cash Desk'],
        ['disbursements', '/cashier/disbursements/index.php', 'bi-cash-stack', 'Disbursements'],
        ['repayments', '/cashier/repayments/index.php', 'bi-calendar-check', 'Repayments'],
    ],
    ROLE_CUSTOMER => [
        ['dashboard', '/customer/dashboard.php', 'bi-speedometer2', 'Dashboard'],
        ['section', null, null, 'My Account'],
        ['profile', '/customer/profile.php', 'bi-person', 'My Profile'],
        ['loans', '/customer/loans.php', 'bi-cash-coin', 'My Loans'],
        ['schedule', '/customer/repayment_schedule.php', 'bi-calendar-check', 'Repayment Schedule'],
        ['payments', '/customer/payment_history.php', 'bi-receipt', 'Payment History'],
    ],
];

$items = $menus[$roleId] ?? [];
?>
<aside class="mfs-sidebar" id="mfsSidebar">
    <div class="brand">
        <span class="mfs-brand-mark" aria-hidden="true">M</span>
        <span class="brand-text">
            <strong>Microfinance</strong>
            <small>LOAN MANAGEMENT</small>
        </span>
    </div>
    <nav aria-label="Main navigation">
        <?php foreach ($items as $item): ?>
            <?php if ($item[0] === 'section'): ?>
                <div class="nav-section-label"><?= e($item[3]) ?></div>
            <?php else: ?>
                <?php nav_link($item[0], $activeMenu, $item[1], $item[2], $item[3]); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>
