<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_ADMIN);

$id = (int) ($_GET['id'] ?? $_POST['application_id'] ?? 0);
$pdo = Database::getConnection();
$user = current_user();

$stmt = $pdo->prepare(
    "SELECT a.*, uc.full_name AS customer_name, c.customer_code, c.monthly_income, lt.type_name, lt.interest_rate,
            lt.min_amount, lt.max_amount, uo.full_name AS officer_name
     FROM loan_applications a
     INNER JOIN customers c ON c.customer_id = a.customer_id
     INNER JOIN users uc ON uc.user_id = c.user_id
     INNER JOIN loan_types lt ON lt.loan_type_id = a.loan_type_id
     INNER JOIN users uo ON uo.user_id = a.submitted_by
     WHERE a.application_id = :id"
);
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    flash('danger', 'Application not found.');
    redirect('/admin/applications/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if ($app['status'] !== 'pending') {
        flash('danger', 'This application has already been reviewed.');
        redirect('/admin/applications/view.php?id=' . $id);
    }

    $decision = clean($_POST['decision'] ?? '');
    $notes = clean($_POST['review_notes'] ?? '');

    if (!in_array($decision, ['approved', 'rejected', 'returned'], true)) {
        $errors[] = 'Please choose a decision.';
    }
    if (in_array($decision, ['rejected', 'returned'], true) && $notes === '') {
        $errors[] = 'Please explain why the application is being rejected or returned.';
    }

    if (empty($errors)) {
        $update = $pdo->prepare(
            'UPDATE loan_applications SET status = :status, review_notes = :notes, reviewed_by = :reviewer, reviewed_at = NOW()
             WHERE application_id = :id'
        );
        $update->execute(['status' => $decision, 'notes' => $notes ?: null, 'reviewer' => $user['user_id'], 'id' => $id]);

        app_log("Admin {$user['username']} set application {$app['application_code']} to {$decision}.");
        flash('success', "Application {$app['application_code']} marked as {$decision}.");
        redirect('/admin/applications/index.php?status=pending');
    }
}

$pageTitle = 'Review Application';
$activeMenu = 'applications';
$breadcrumb = ['Applications' => '/admin/applications/index.php', $app['application_code'] => null];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="mfs-card p-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h2 class="h5 mb-0"><?= e($app['application_code']) ?></h2>
                <?= status_badge($app['status']) ?>
            </div>
            <dl class="row small mb-0">
                <dt class="col-4">Customer</dt><dd class="col-8"><?= e($app['customer_name']) ?> (<?= e($app['customer_code']) ?>)</dd>
                <dt class="col-4">Monthly Income</dt><dd class="col-8"><?= $app['monthly_income'] !== null ? money($app['monthly_income']) : '—' ?></dd>
                <dt class="col-4">Loan Type</dt><dd class="col-8"><?= e($app['type_name']) ?> — <?= number_format((float) $app['interest_rate'], 2) ?>% p.a.</dd>
                <dt class="col-4">Type Amount Range</dt><dd class="col-8"><?= money($app['min_amount']) ?> – <?= money($app['max_amount']) ?></dd>
                <dt class="col-4">Requested Amount</dt><dd class="col-8"><?= money($app['requested_amount']) ?></dd>
                <dt class="col-4">Requested Duration</dt><dd class="col-8"><?= (int) $app['requested_duration_months'] ?> months</dd>
                <dt class="col-4">Purpose</dt><dd class="col-8"><?= e($app['purpose'] ?? '—') ?></dd>
                <dt class="col-4">Submitted By</dt><dd class="col-8"><?= e($app['officer_name']) ?> on <?= display_date($app['created_at']) ?></dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="mfs-card p-3">
            <h2 class="h6 mb-3">Decision</h2>
            <?php if ($app['status'] !== 'pending'): ?>
                <p class="text-muted small">This application was already reviewed
                    (<?= status_badge($app['status']) ?>)<?php if ($app['review_notes']): ?> — "<?= e($app['review_notes']) ?>"<?php endif; ?>.</p>
            <?php else: ?>
                <form method="post" class="needs-validation" novalidate>
                    <?php csrf_field(); ?>
                    <input type="hidden" name="application_id" value="<?= $id ?>">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="review_notes" class="form-control" rows="3" placeholder="Required for reject/return"></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="decision" value="approved" class="btn btn-navy">Approve</button>
                        <button type="submit" name="decision" value="returned" class="btn btn-outline-secondary">Return for Correction</button>
                        <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger">Reject</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
