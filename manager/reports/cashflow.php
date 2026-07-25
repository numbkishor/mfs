<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_MANAGER);

$fromDate = clean($_GET['from'] ?? date('Y-m-01'));
$toDate = clean($_GET['to'] ?? date('Y-m-d'));

$summary = get_cash_flow_summary($fromDate, $toDate);
$allTime = get_cash_flow_summary();
$monthly = get_monthly_cash_flow(12);

$pageTitle = 'Cash Flow Report';
$activeMenu = 'cashflow';
$breadcrumb = ['Reports' => '/manager/reports/index.php', 'Cash Flow' => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mfs-card p-3 mb-4 d-print-none">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">From</label>
            <input type="date" name="from" class="form-control" value="<?= e($fromDate) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">To</label>
            <input type="date" name="to" class="form-control" value="<?= e($toDate) ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-navy w-100">Apply</button>
        </div>
        <div class="col-md-2 ms-auto">
            <button type="button" class="btn btn-outline-secondary w-100" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </div>
    </form>
</div>

<h2 class="h6 mb-2">Period: <?= display_date($fromDate) ?> &ndash; <?= display_date($toDate) ?></h2>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile">
            <div class="stat-label">Disbursed (period)</div>
            <div class="stat-value" style="font-size:1.3rem;"><?= money($summary['total_disbursed']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile accent-success">
            <div class="stat-label">Repaid (period)</div>
            <div class="stat-value" style="font-size:1.3rem;"><?= money($summary['total_repaid']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile accent-brass">
            <div class="stat-label">Interest Collected (period)</div>
            <div class="stat-value" style="font-size:1.3rem;"><?= money($summary['interest_collected']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="mfs-stat-tile accent-danger">
            <div class="stat-label">Net Cash Movement (period)</div>
            <div class="stat-value" style="font-size:1.3rem;"><?= money($summary['available_cash']) ?></div>
        </div>
    </div>
</div>

<div class="mfs-card p-3 mb-4">
    <div class="stat-label">All-Time Available Cash</div>
    <div class="stat-value"><?= money($allTime['available_cash']) ?></div>
    <p class="text-muted small mb-0">Total repaid (<?= money($allTime['total_repaid']) ?>) minus total disbursed (<?= money($allTime['total_disbursed']) ?>) across the entire portfolio.</p>
</div>

<div class="mfs-card p-3">
    <h2 class="h6 mb-3">Monthly Breakdown — last 12 months</h2>
    <div class="table-responsive">
        <table class="table table-sm mfs-table align-middle">
            <thead><tr><th>Month</th><th>Disbursed</th><th>Repaid</th><th>Net</th></tr></thead>
            <tbody>
            <?php foreach ($monthly as $m): ?>
                <tr>
                    <td><?= e($m['month']) ?></td>
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
