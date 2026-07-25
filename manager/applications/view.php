<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_MANAGER);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::getConnection();

$stmt = $pdo->prepare(
    "SELECT a.*, uc.full_name AS customer_name, c.customer_code, lt.type_name, lt.interest_rate,
            uo.full_name AS officer_name, ur.full_name AS reviewer_name
     FROM loan_applications a
     INNER JOIN customers c ON c.customer_id = a.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     INNER JOIN loan_types lt ON lt.loan_type_id = a.loan_type_id
     INNER JOIN users uo ON uo.user_id = a.submitted_by
     LEFT JOIN users ur ON ur.user_id = a.reviewed_by
     WHERE a.application_id = :id"
);
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    flash('danger', 'Application not found.');
    redirect('/manager/applications/index.php');
}

$pageTitle = 'Application ' . $app['application_code'];
$activeMenu = 'applications';
$breadcrumb = ['Applications' => '/manager/applications/index.php', $app['application_code'] => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="mfs-card p-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h2 class="h5 mb-0"><?= e($app['application_code']) ?></h2>
                <?= status_badge($app['status']) ?>
            </div>
            <dl class="row small mb-0">
                <dt class="col-4">Customer</dt><dd class="col-8"><?= e($app['customer_name']) ?> (<?= e($app['customer_code']) ?>)</dd>
                <dt class="col-4">Loan Type</dt><dd class="col-8"><?= e($app['type_name']) ?> — <?= number_format((float) $app['interest_rate'], 2) ?>% p.a.</dd>
                <dt class="col-4">Requested Amount</dt><dd class="col-8"><?= money($app['requested_amount']) ?></dd>
                <dt class="col-4">Requested Duration</dt><dd class="col-8"><?= (int) $app['requested_duration_months'] ?> months</dd>
                <dt class="col-4">Purpose</dt><dd class="col-8"><?= e($app['purpose'] ?? '—') ?></dd>
                <dt class="col-4">Submitted By</dt><dd class="col-8"><?= e($app['officer_name']) ?> on <?= display_date($app['created_at']) ?></dd>
                <?php if ($app['reviewed_by']): ?>
                    <dt class="col-4">Reviewed By</dt><dd class="col-8"><?= e($app['reviewer_name']) ?> on <?= display_date($app['reviewed_at']) ?></dd>
                    <dt class="col-4">Review Notes</dt><dd class="col-8"><?= e($app['review_notes'] ?? '—') ?></dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="mfs-card p-3">
            <h2 class="h6 mb-3">Next Steps</h2>
            <?php if ($app['status'] === 'approved'): ?>
                <p class="text-muted small">This application has been approved. The assigned loan officer will generate the loan and arrange disbursement.</p>
            <?php elseif ($app['status'] === 'returned'): ?>
                <p class="text-muted small">This application was returned to the loan officer for correction.</p>
            <?php elseif ($app['status'] === 'rejected'): ?>
                <p class="text-muted small">This application was rejected. See the review notes for the reason.</p>
            <?php else: ?>
                <p class="text-muted small">Awaiting review.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
