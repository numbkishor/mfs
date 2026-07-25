<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_login('employee');
require_role(ROLE_OFFICER);

$applicationId = (int) ($_GET['application_id'] ?? $_POST['application_id'] ?? 0);
$pdo = Database::getConnection();
$user = current_user();

$stmt = $pdo->prepare(
    "SELECT a.*, lt.type_name, lt.interest_rate
     FROM loan_applications a INNER JOIN loan_types lt ON lt.loan_type_id = a.loan_type_id
     WHERE a.application_id = :id"
);
$stmt->execute(['id' => $applicationId]);
$app = $stmt->fetch();

if (!$app) {
    flash('danger', 'Application not found.');
    redirect('/officer/applications/index.php');
}

if ($app['status'] !== 'approved') {
    flash('danger', 'Only approved applications can be converted into a loan.');
    redirect('/officer/applications/view.php?id=' . $applicationId);
}

$existing = $pdo->prepare('SELECT loan_id FROM loans WHERE application_id = :id');
$existing->execute(['id' => $applicationId]);
if ($existing->fetchColumn()) {
    flash('info', 'A loan has already been generated for this application.');
    redirect('/officer/loans/index.php');
}

$errors = [];
$old = ['start_date' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old['start_date'] = clean($_POST['start_date'] ?? $old['start_date']);

    if (strtotime($old['start_date']) === false) {
        $errors[] = 'Please provide a valid start date.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $principal = (float) $app['requested_amount'];
            $rate = (float) $app['interest_rate'];
            $months = (int) $app['requested_duration_months'];
            $emi = calculate_emi($principal, $rate, $months);
            $endDate = date('Y-m-d', strtotime($old['start_date'] . " +{$months} months"));
            $loanCode = generate_code('loans', 'loan_code', 'LN', 6);

            $loanStmt = $pdo->prepare(
                'INSERT INTO loans (loan_code, application_id, customer_id, loan_type_id, principal_amount, interest_rate,
                 duration_months, emi_amount, start_date, end_date, status, approved_by)
                 VALUES (:code, :app_id, :customer_id, :loan_type_id, :principal, :rate, :months, :emi, :start, :end, "active", :approved_by)'
            );
            $loanStmt->execute([
                'code' => $loanCode, 'app_id' => $applicationId, 'customer_id' => $app['customer_id'],
                'loan_type_id' => $app['loan_type_id'], 'principal' => $principal, 'rate' => $rate, 'months' => $months,
                'emi' => $emi, 'start' => $old['start_date'], 'end' => $endDate, 'approved_by' => $app['reviewed_by'] ?? $user['user_id'],
            ]);
            $loanId = (int) $pdo->lastInsertId();

            $schedule = build_amortization_schedule($principal, $rate, $months, $old['start_date']);
            $scheduleStmt = $pdo->prepare(
                'INSERT INTO repayment_schedules (loan_id, installment_no, due_date, principal_due, interest_due, total_due, amount_paid, status)
                 VALUES (:loan_id, :no, :due, :principal, :interest, :total, 0, "pending")'
            );
            foreach ($schedule as $line) {
                $scheduleStmt->execute([
                    'loan_id' => $loanId, 'no' => $line['installment_no'], 'due' => $line['due_date'],
                    'principal' => $line['principal_due'], 'interest' => $line['interest_due'], 'total' => $line['total_due'],
                ]);
            }

            $pdo->commit();
            app_log("Officer {$user['username']} generated loan {$loanCode} from application {$app['application_code']}.");
            flash('success', "Loan {$loanCode} generated. The cashier can now disburse it from the cashier desk.");
            redirect('/officer/loans/view.php?id=' . $loanId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            $errors[] = 'Something went wrong while generating the loan. Please try again.';
        }
    }
}

$pageTitle = 'Generate Loan';
$activeMenu = 'loans';
$breadcrumb = ['Applications' => '/officer/applications/index.php', $app['application_code'] => '/officer/applications/view.php?id=' . $applicationId, 'Generate Loan' => null];
require_once __DIR__ . '/../../includes/header.php';

$previewEmi = calculate_emi((float) $app['requested_amount'], (float) $app['interest_rate'], (int) $app['requested_duration_months']);
?>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="mfs-card p-3" style="max-width: 640px;">
    <dl class="row small mb-3">
        <dt class="col-5">Loan Type</dt><dd class="col-7"><?= e($app['type_name']) ?></dd>
        <dt class="col-5">Principal</dt><dd class="col-7"><?= money($app['requested_amount']) ?></dd>
        <dt class="col-5">Interest Rate</dt><dd class="col-7"><?= number_format((float) $app['interest_rate'], 2) ?>% p.a.</dd>
        <dt class="col-5">Duration</dt><dd class="col-7"><?= (int) $app['requested_duration_months'] ?> months</dd>
        <dt class="col-5">Estimated EMI</dt><dd class="col-7 fw-semibold"><?= money($previewEmi) ?> / month</dd>
    </dl>
    <form method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
        <div class="mb-3">
            <label class="form-label">Loan Start Date</label>
            <input type="date" name="start_date" class="form-control" required value="<?= e($old['start_date']) ?>">
            <div class="form-text">The first EMI will be due one month after this date.</div>
        </div>
        <button type="submit" class="btn btn-brass px-4">Generate Loan &amp; EMI Schedule</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
