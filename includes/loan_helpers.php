<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Calculate the fixed monthly EMI (equal monthly installment) for a
 * reducing-balance loan using the standard amortization formula:
 *
 *   EMI = P * r * (1+r)^n / ((1+r)^n - 1)
 *
 * where r is the *monthly* interest rate and n the number of months.
 */
function calculate_emi(float $principal, float $annualRatePct, int $months): float
{
    if ($months <= 0) {
        throw new InvalidArgumentException('Loan duration must be at least 1 month.');
    }

    $monthlyRate = ($annualRatePct / 100) / 12;

    if ($monthlyRate == 0.0) {
        return round($principal / $months, 2);
    }

    $factor = (1 + $monthlyRate) ** $months;
    $emi = ($principal * $monthlyRate * $factor) / ($factor - 1);

    return round($emi, 2);
}

/**
 * Build a full reducing-balance amortization schedule.
 *
 * @return array<int, array{installment_no:int, due_date:string, principal_due:float, interest_due:float, total_due:float}>
 */
function build_amortization_schedule(float $principal, float $annualRatePct, int $months, string $startDate): array
{
    $monthlyRate = ($annualRatePct / 100) / 12;
    $emi = calculate_emi($principal, $annualRatePct, $months);
    $balance = $principal;
    $schedule = [];
    $dueTimestamp = strtotime($startDate);

    for ($i = 1; $i <= $months; $i++) {
        $dueTimestamp = strtotime('+1 month', $dueTimestamp);
        $interestDue = round($balance * $monthlyRate, 2);
        $principalDue = round($emi - $interestDue, 2);

        // Absorb rounding drift into the final installment so the
        // schedule always sums exactly to the principal.
        if ($i === $months) {
            $principalDue = round($balance, 2);
        }

        $totalDue = round($principalDue + $interestDue, 2);
        $balance = round($balance - $principalDue, 2);

        $schedule[] = [
            'installment_no' => $i,
            'due_date'       => date('Y-m-d', $dueTimestamp),
            'principal_due'  => $principalDue,
            'interest_due'   => $interestDue,
            'total_due'      => $totalDue,
        ];
    }

    return $schedule;
}

/**
 * Recompute a schedule line's status from amount_paid vs total_due,
 * factoring in whether the due date has passed.
 */
function schedule_line_status(float $totalDue, float $amountPaid, string $dueDate): string
{
    if ($amountPaid >= $totalDue) {
        return 'paid';
    }

    if ($amountPaid > 0) {
        return 'partial';
    }

    return strtotime($dueDate) < strtotime(date('Y-m-d')) ? 'overdue' : 'pending';
}

/**
 * Cash flow figures, always computed live from disbursements and
 * payments — there is intentionally no stored cash_flow table.
 *
 * @return array{total_disbursed:float, total_repaid:float, interest_collected:float, principal_repaid:float, available_cash:float}
 */
function get_cash_flow_summary(?string $fromDate = null, ?string $toDate = null): array
{
    $pdo = Database::getConnection();

    $disbSql = 'SELECT COALESCE(SUM(disbursed_amount),0) FROM disbursements WHERE 1=1';
    $payPrincipalSql = "SELECT COALESCE(SUM(rs.principal_due * (p.amount_paid / rs.total_due)),0)
                         FROM payments p INNER JOIN repayment_schedules rs ON rs.schedule_id = p.schedule_id WHERE 1=1";
    $payInterestSql = "SELECT COALESCE(SUM(rs.interest_due * (p.amount_paid / rs.total_due)),0)
                        FROM payments p INNER JOIN repayment_schedules rs ON rs.schedule_id = p.schedule_id WHERE 1=1";
    $payTotalSql = 'SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE 1=1';

    $params = [];
    $dateFilterDisb = '';
    $dateFilterPay = '';

    if ($fromDate) {
        $dateFilterDisb .= ' AND disbursement_date >= :from';
        $dateFilterPay .= ' AND p.payment_date >= :from';
        $params['from'] = $fromDate;
    }
    if ($toDate) {
        $dateFilterDisb .= ' AND disbursement_date <= :to';
        $dateFilterPay .= ' AND p.payment_date <= :to';
        $params['to'] = $toDate;
    }

    $stmt = $pdo->prepare($disbSql . $dateFilterDisb);
    $stmt->execute($params);
    $totalDisbursed = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare($payPrincipalSql . $dateFilterPay);
    $stmt->execute($params);
    $principalRepaid = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare($payInterestSql . $dateFilterPay);
    $stmt->execute($params);
    $interestCollected = (float) $stmt->fetchColumn();

    $totalPayFilter = str_replace('p.payment_date', 'payment_date', $dateFilterPay);
    $stmt = $pdo->prepare($payTotalSql . $totalPayFilter);
    $stmt->execute($params);
    $totalRepaid = (float) $stmt->fetchColumn();

    return [
        'total_disbursed'    => $totalDisbursed,
        'total_repaid'       => $totalRepaid,
        'principal_repaid'   => $principalRepaid,
        'interest_collected' => $interestCollected,
        'available_cash'     => $totalRepaid - $totalDisbursed,
    ];
}

/**
 * Monthly cash-flow breakdown for the last N months, computed live via SQL
 * (used by the cash flow report + dashboard charts).
 */
function get_monthly_cash_flow(int $months = 6): array
{
    $pdo = Database::getConnection();

    $disbStmt = $pdo->prepare(
        "SELECT DATE_FORMAT(disbursement_date, '%Y-%m') AS ym, COALESCE(SUM(disbursed_amount),0) AS total
         FROM disbursements
         WHERE disbursement_date >= DATE_SUB(CURDATE(), INTERVAL :m MONTH)
         GROUP BY ym"
    );
    $disbStmt->execute(['m' => $months]);
    $disbByMonth = [];
    foreach ($disbStmt->fetchAll() as $row) {
        $disbByMonth[$row['ym']] = (float) $row['total'];
    }

    $payStmt = $pdo->prepare(
        "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, COALESCE(SUM(amount_paid),0) AS total
         FROM payments
         WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL :m MONTH)
         GROUP BY ym"
    );
    $payStmt->execute(['m' => $months]);
    $payByMonth = [];
    foreach ($payStmt->fetchAll() as $row) {
        $payByMonth[$row['ym']] = (float) $row['total'];
    }

    $result = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-{$i} months"));
        $result[] = [
            'month'        => date('M Y', strtotime($ym . '-01')),
            'disbursed'    => $disbByMonth[$ym] ?? 0.0,
            'repaid'       => $payByMonth[$ym] ?? 0.0,
        ];
    }

    return $result;
}

/**
 * Outstanding balance for a single loan: sum of total_due minus
 * amount_paid across all its schedule lines.
 */
function get_loan_outstanding_balance(int $loanId): float
{
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(total_due - amount_paid),0) FROM repayment_schedules WHERE loan_id = :id');
    $stmt->execute(['id' => $loanId]);

    return (float) $stmt->fetchColumn();
}

/**
 * Refresh overdue statuses across all schedule lines whose due date has
 * passed and are not fully paid. Safe to call on every dashboard load.
 */
function refresh_overdue_statuses(): void
{
    $pdo = Database::getConnection();
    $pdo->exec(
        "UPDATE repayment_schedules
         SET status = 'overdue'
         WHERE due_date < CURDATE() AND amount_paid < total_due AND status NOT IN ('overdue')"
    );
    $pdo->exec(
        "UPDATE repayment_schedules
         SET status = 'pending'
         WHERE due_date >= CURDATE() AND amount_paid = 0 AND status = 'overdue'"
    );
}
