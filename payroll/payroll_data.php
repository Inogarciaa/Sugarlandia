<?php
require '../config.php';

$employee_id         = (int)$_POST['employee_id'];
$gross_pay           = (float)$_POST['gross_pay'];
$net_pay             = (float)$_POST['net_pay'];
$payment_date        = date('Y-m-d');
$pay_period_start    = !empty($_POST['pay_period_start']) ? trim($_POST['pay_period_start']) : date('Y-m-01');
$pay_period_end      = !empty($_POST['pay_period_end']) ? trim($_POST['pay_period_end']) : date('Y-m-t');
$sss_contribution    = (float)$_POST['sss_contribution'];
$philhealth_contribution = (float)$_POST['philhealth_contribution'];
$pagibig_contribution    = (float)$_POST['pagibig_contribution'];

if (empty($employee_id) || empty($gross_pay) || empty($net_pay) || empty($payment_date)) {
    die("<p style='color:red;font-family:Arial'>❌ All fields must be filled up. <a href='payroll.php'>Go back</a></p>");
}
function computeSSS(float $gross): float {
    $table = [
        [0,       3249.99,  135.00],
        [3250,    3749.99,  157.50],
        [3750,    4249.99,  180.00],
        [4250,    4749.99,  202.50],
        [4750,    5249.99,  225.00],
        [5250,    5749.99,  247.50],
        [5750,    6249.99,  270.00],
        [6250,    6749.99,  292.50],
        [6750,    7249.99,  315.00],
        [7250,    7749.99,  337.50],
        [7750,    8249.99,  360.00],
        [8250,    8749.99,  382.50],
        [8750,    9249.99,  405.00],
        [9250,    9749.99,  427.50],
        [9750,   10249.99,  450.00],
        [10250,  10749.99,  472.50],
        [10750,  11249.99,  495.00],
        [11250,  11749.99,  517.50],
        [11750,  12249.99,  540.00],
        [12250,  12749.99,  562.50],
        [12750,  13249.99,  585.00],
        [13250,  13749.99,  607.50],
        [13750,  14249.99,  630.00],
        [14250,  14749.99,  652.50],
        [14750,  15249.99,  675.00],
        [15250,  15749.99,  697.50],
        [15750,  16249.99,  720.00],
        [16250,  16749.99,  742.50],
        [16750,  17249.99,  765.00],
        [17250,  17749.99,  787.50],
        [17750,  18249.99,  810.00],
        [18250,  18749.99,  832.50],
        [18750,  19249.99,  855.00],
        [19250,  19749.99,  877.50],
        [19750,  20249.99,  900.00],
        [20250,  20749.99,  922.50],
        [20750,  21249.99,  945.00],
        [21250,  21749.99,  967.50],
        [21750,  22249.99,  990.00],
        [22250,  22749.99, 1012.50],
        [22750,  23249.99, 1035.00],
        [23250,  23749.99, 1057.50],
        [23750,  24249.99, 1080.00],
        [24250,  24749.99, 1102.50],
        [24750,  25249.99, 1125.00],
        [25250,  25749.99, 1147.50],
        [25750,  26249.99, 1170.00],
        [26250,  26749.99, 1192.50],
        [26750,  27249.99, 1215.00],
        [27250,  27749.99, 1237.50],
        [27750,  28249.99, 1260.00],
        [28250,  28749.99, 1282.50],
        [28750,  29249.99, 1305.00],
        [29250,  29749.99, 1327.50],
        [29750,  PHP_INT_MAX, 1350.00],
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
    $rate   = $gross <= 1500 ? 0.01 : 0.02;
    $contrib = $gross * $rate;
    if ($contrib > 100) $contrib = 100;
    return round($contrib, 2);
}

// Recompute gross pay server-side from attendance (daily_rate × days worked)
$attendance_deduction = 0.00;
try {
    $empStmt = $pdo->prepare("
        SELECT COALESCE(p.daily_rate, ROUND(p.base_salary / 26, 2)) AS daily_rate
        FROM employees e
        JOIN positions p ON p.position_id = e.position_id
        WHERE e.employee_id = :eid LIMIT 1
    ");
    $empStmt->execute([':eid' => $employee_id]);
    $dailyRate = (float)$empStmt->fetchColumn();

    $currentMonth = date('Y-m');

    // Count attendance statuses this month
    $attStmt = $pdo->prepare("
        SELECT status, COUNT(*) as cnt
        FROM attendance
        WHERE employee_id = :eid
          AND DATE_FORMAT(date, '%Y-%m') = :month
        GROUP BY status
    ");
    $attStmt->execute([':eid' => $employee_id, ':month' => $currentMonth]);
    $attCounts = $attStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $presentCount = (int)($attCounts['Present'] ?? 0);
    $lateCount    = (int)($attCounts['Late'] ?? 0);
    $halfDayCount = (int)($attCounts['Half-Day'] ?? 0);

    // Days worked: Present + Late = full day, Half-Day = 0.5
    $daysWorked = $presentCount + $lateCount + ($halfDayCount * 0.5);

    // Gross pay = daily rate × days worked
    $gross_pay = round($dailyRate * $daysWorked, 2);

} catch (Exception $e) {
    // Keep the gross_pay from POST if DB lookup fails
}

$sss_contribution        = computeSSS($gross_pay);
$philhealth_contribution = computePhilHealth($gross_pay);
$pagibig_contribution    = computePagIbig($gross_pay);

$total_deductions = $sss_contribution + $philhealth_contribution + $pagibig_contribution;
$net_pay          = round($gross_pay - $total_deductions, 2);

try {
    $sql = "INSERT INTO payroll
                (employee_id, gross_pay, sss_contribution, philhealth_contribution,
                 pagibig_contribution, attendance_deduction, total_deductions, net_pay,
                 pay_period_start, pay_period_end, payment_date)
            VALUES
                (:employee_id, :gross_pay, :sss, :philhealth,
                 :pagibig, :att_ded, :total_ded, :net_pay,
                 :ps, :pe, :payment_date)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':employee_id' => $employee_id,
        ':gross_pay'   => $gross_pay,
        ':sss'         => $sss_contribution,
        ':philhealth'  => $philhealth_contribution,
        ':pagibig'     => $pagibig_contribution,
        ':att_ded'     => $attendance_deduction,
        ':total_ded'   => $total_deductions,
        ':net_pay'     => $net_pay,
        ':ps'          => $pay_period_start,
        ':pe'          => $pay_period_end,
        ':payment_date'=> $payment_date,
    ]);

    $newId = $pdo->lastInsertId();
    header("Location: ../index.php?success=Payroll+entry+saved+successfully+(ID:+$newId)");
    exit;

} catch (PDOException $e) {
    echo "<p style='color:red;font-family:Arial'>❌ Error: " . htmlspecialchars($e->getMessage()) . " <a href='payroll.php'>Go back</a></p>";
}
?>