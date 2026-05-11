<?php
/**
 * seed_mar_apr.php
 * Generate payroll records for March & April 2026 (all active employees)
 */
require __DIR__ . '/../config.php';

function computeSSS(float $g): float {
    $t = [[0,3249.99,135],[3250,3749.99,157.50],[3750,4249.99,180],[4250,4749.99,202.50],
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
        [28750,29249.99,1305],[29250,29749.99,1327.50],[29750,PHP_INT_MAX,1350]];
    foreach ($t as $r) { if ($g >= $r[0] && $g <= $r[1]) return $r[2]; }
    return 1350;
}
function computePH(float $g): float { return round(max(500, min(2500, $g * 0.025)), 2); }
function computePI(float $g): float { return round(min(100, $g * ($g <= 1500 ? 0.01 : 0.02)), 2); }

$employees = $pdo->query("
    SELECT e.employee_id, e.first_name, e.last_name,
           COALESCE(p.daily_rate, ROUND(p.base_salary / 26, 2)) AS daily_rate
    FROM employees e
    JOIN positions p ON p.position_id = e.position_id
    WHERE e.is_archived = 0
")->fetchAll();

$months = ['2026-03', '2026-04'];

$attStmt = $pdo->prepare("
    SELECT status, COUNT(*) as cnt FROM attendance
    WHERE employee_id = :eid AND DATE_FORMAT(date, '%Y-%m') = :month
    GROUP BY status
");
$checkPay = $pdo->prepare("SELECT COUNT(*) FROM payroll WHERE employee_id = :eid AND pay_period_start = :ps AND pay_period_end = :pe");
$insertPay = $pdo->prepare("
    INSERT INTO payroll (employee_id, gross_pay, sss_contribution, philhealth_contribution,
        pagibig_contribution, attendance_deduction, total_deductions, net_pay,
        pay_period_start, pay_period_end, payment_date, is_paid, paid_date)
    VALUES (:eid, :gp, :sss, :ph, :pi, 0, :td, :np, :ps, :pe, :pd, 0, NULL)
");

echo "=== Generating payroll for March & April 2026 ===\n\n";

$pdo->beginTransaction();
try {
    foreach ($months as $month) {
        $ps = $month . '-01';
        $pe = date('Y-m-t', strtotime($ps));
        $pd = $pe;
        echo "-- $month ($ps to $pe) --\n";

        foreach ($employees as $emp) {
            $eid = $emp['employee_id'];
            $name = $emp['first_name'] . ' ' . $emp['last_name'];
            $dailyRate = (float)$emp['daily_rate'];

            // Check if payroll already exists
            $checkPay->execute([':eid' => $eid, ':ps' => $ps, ':pe' => $pe]);
            if ($checkPay->fetchColumn() > 0) {
                echo "   SKIP $name (already exists)\n";
                continue;
            }

            // Get attendance
            $attStmt->execute([':eid' => $eid, ':month' => $month]);
            $attCounts = $attStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $present  = (int)($attCounts['Present'] ?? 0);
            $late     = (int)($attCounts['Late'] ?? 0);
            $halfDay  = (int)($attCounts['Half-Day'] ?? 0);
            $absent   = (int)($attCounts['Absent'] ?? 0);
            $daysWorked = $present + $late + ($halfDay * 0.5);

            if ($daysWorked <= 0 && $absent <= 0) {
                echo "   SKIP $name (no attendance)\n";
                continue;
            }

            $grossPay = round($dailyRate * $daysWorked, 2);
            $sss = computeSSS($grossPay);
            $ph  = computePH($grossPay);
            $pi  = computePI($grossPay);
            $td  = round($sss + $ph + $pi, 2);
            $np  = round($grossPay - $td, 2);

            $insertPay->execute([
                ':eid' => $eid, ':gp' => $grossPay, ':sss' => $sss,
                ':ph' => $ph, ':pi' => $pi, ':td' => $td, ':np' => $np,
                ':ps' => $ps, ':pe' => $pe, ':pd' => $pd,
            ]);
            echo "   OK $name: {$daysWorked}d worked, Gross=" . number_format($grossPay,2) . ", Net=" . number_format($np,2) . " [Unpaid]\n";
        }
        echo "\n";
    }
    $pdo->commit();
    echo "=== Done! ===\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
