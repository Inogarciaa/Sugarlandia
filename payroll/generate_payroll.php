<?php
/*
 * generate_payroll.php
 * AJAX endpoint — Bulk-generates payroll for ALL active employees for a given month.
 * POST: { month: "YYYY-MM" }
 *
 * For each employee:
 *   1. Looks up daily_rate from their position
 *   2. Counts attendance for the month (Present, Late, Half-Day, Absent)
 *   3. Calculates gross_pay = daily_rate × days_worked
 *   4. Computes SSS, PhilHealth, Pag-IBIG deductions
 *   5. Inserts payroll record (skips if one already exists for that employee+month)
 *
 * Returns JSON with summary of what was generated/skipped.
 */
require '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']);
    exit;
}

$month = isset($_POST['month']) ? trim($_POST['month']) : '';

// Validate month format YYYY-MM
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    echo json_encode(['error' => 'Invalid month format. Expected YYYY-MM.']);
    exit;
}

$periodStart = $month . '-01';
$periodEnd   = date('Y-m-t', strtotime($periodStart));
$paymentDate = date('Y-m-d'); // today

// ── Deduction functions ──

function computeSSS(float $gross): float {
    $table = [
        [0,3249.99,135],[3250,3749.99,157.50],[3750,4249.99,180],[4250,4749.99,202.50],
        [4750,5249.99,225],[5250,5749.99,247.50],[5750,6249.99,270],[6250,6749.99,292.50],
        [6750,7249.99,315],[7250,7749.99,337.50],[7750,8249.99,360],[8250,8749.99,382.50],
        [8750,9249.99,405],[9250,9749.99,427.50],[9750,10249.99,450],[10250,10749.99,472.50],
        [10750,11249.99,495],[11250,11749.99,517.50],[11750,12249.99,540],[12250,12749.99,562.50],
        [12750,13249.99,585],[13250,13749.99,607.50],[13750,14249.99,630],[14250,14749.99,652.50],
        [14750,15249.99,675],[15250,15749.99,697.50],[15750,16249.99,720],[16250,16749.99,742.50],
        [16750,17249.99,765],[17250,17749.99,787.50],[17750,18249.99,810],[18250,18749.99,832.50],
        [18750,19249.99,855],[19250,19749.99,877.50],[19750,20249.99,900],[20250,20749.99,922.50],
        [20750,21249.99,945],[21250,21749.99,967.50],[21750,22249.99,990],[22250,22749.99,1012.50],
        [22750,23249.99,1035],[23250,23749.99,1057.50],[23750,24249.99,1080],[24250,24749.99,1102.50],
        [24750,25249.99,1125],[25250,25749.99,1147.50],[25750,26249.99,1170],[26250,26749.99,1192.50],
        [26750,27249.99,1215],[27250,27749.99,1237.50],[27750,28249.99,1260],[28250,28749.99,1282.50],
        [28750,29249.99,1305],[29250,29749.99,1327.50],[29750,PHP_INT_MAX,1350],
    ];
    foreach ($table as $row) {
        if ($gross >= $row[0] && $gross <= $row[1]) return $row[2];
    }
    return 1350.00;
}

function computePhilHealth(float $gross): float {
    $contrib = $gross * 0.025;
    if ($contrib < 500)  $contrib = 500;
    if ($contrib > 2500) $contrib = 2500;
    return round($contrib, 2);
}

function computePagIbig(float $gross): float {
    $rate = $gross <= 1500 ? 0.01 : 0.02;
    $contrib = $gross * $rate;
    if ($contrib > 100) $contrib = 100;
    return round($contrib, 2);
}

try {
    // Get all active employees with their daily rate
    $empStmt = $pdo->query("
        SELECT e.employee_id, e.first_name, e.last_name,
               COALESCE(p.daily_rate, ROUND(p.base_salary / 26, 2)) AS daily_rate
        FROM employees e
        JOIN positions p ON p.position_id = e.position_id
        WHERE e.is_archived = 0
        ORDER BY e.first_name ASC
    ");
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($employees)) {
        echo json_encode(['error' => 'No active employees found.']);
        exit;
    }

    // Check which employees already have payroll for this month
    $existingStmt = $pdo->prepare("
        SELECT employee_id FROM payroll
        WHERE pay_period_start = :ps AND pay_period_end = :pe
    ");
    $existingStmt->execute([':ps' => $periodStart, ':pe' => $periodEnd]);
    $existingIds = $existingStmt->fetchAll(PDO::FETCH_COLUMN);
    $existingSet = array_flip($existingIds);

    // Prepare attendance query
    $attStmt = $pdo->prepare("
        SELECT status, COUNT(*) as cnt
        FROM attendance
        WHERE employee_id = :eid
          AND DATE_FORMAT(date, '%Y-%m') = :month
        GROUP BY status
    ");

    // Prepare insert
    $insertStmt = $pdo->prepare("
        INSERT INTO payroll
            (employee_id, gross_pay, sss_contribution, philhealth_contribution,
             pagibig_contribution, attendance_deduction, total_deductions, net_pay,
             pay_period_start, pay_period_end, payment_date)
        VALUES
            (:employee_id, :gross_pay, :sss, :philhealth,
             :pagibig, :att_ded, :total_ded, :net_pay,
             :ps, :pe, :payment_date)
    ");

    $generated = [];
    $skipped   = [];
    $noAttendance = [];

    $pdo->beginTransaction();

    foreach ($employees as $emp) {
        $empId = (int)$emp['employee_id'];
        $empName = $emp['first_name'] . ' ' . $emp['last_name'];

        // Skip if already has payroll for this month
        if (isset($existingSet[$empId])) {
            $skipped[] = ['name' => $empName, 'reason' => 'Already has payroll for this period'];
            continue;
        }

        // Get attendance for this month
        $attStmt->execute([':eid' => $empId, ':month' => $month]);
        $attCounts = $attStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $presentCount = (int)($attCounts['Present'] ?? 0);
        $lateCount    = (int)($attCounts['Late'] ?? 0);
        $halfDayCount = (int)($attCounts['Half-Day'] ?? 0);
        $absentCount  = (int)($attCounts['Absent'] ?? 0);

        $daysWorked = $presentCount + $lateCount + ($halfDayCount * 0.5);

        // Skip if no attendance at all
        if ($daysWorked <= 0 && $absentCount <= 0) {
            $noAttendance[] = ['name' => $empName, 'reason' => 'No attendance records'];
            continue;
        }

        $dailyRate = (float)$emp['daily_rate'];
        $grossPay  = round($dailyRate * $daysWorked, 2);

        // Compute deductions
        $sss        = computeSSS($grossPay);
        $philhealth = computePhilHealth($grossPay);
        $pagibig    = computePagIbig($grossPay);
        $totalDed   = round($sss + $philhealth + $pagibig, 2);
        $netPay     = round($grossPay - $totalDed, 2);

        $insertStmt->execute([
            ':employee_id'  => $empId,
            ':gross_pay'    => $grossPay,
            ':sss'          => $sss,
            ':philhealth'   => $philhealth,
            ':pagibig'      => $pagibig,
            ':att_ded'      => 0.00,
            ':total_ded'    => $totalDed,
            ':net_pay'      => $netPay,
            ':ps'           => $periodStart,
            ':pe'           => $periodEnd,
            ':payment_date' => $paymentDate,
        ]);

        $generated[] = [
            'name'       => $empName,
            'days_worked'=> $daysWorked,
            'gross_pay'  => $grossPay,
            'net_pay'    => $netPay,
        ];
    }

    $pdo->commit();

    echo json_encode([
        'success'       => true,
        'month'         => $month,
        'generated'     => $generated,
        'skipped'       => $skipped,
        'no_attendance' => $noAttendance,
        'summary'       => [
            'total_employees' => count($employees),
            'generated_count' => count($generated),
            'skipped_count'   => count($skipped),
            'no_attendance_count' => count($noAttendance),
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>
