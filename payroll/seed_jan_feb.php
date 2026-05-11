<?php
/**
 * seed_jan_feb.php
 * One-time script to populate January & February 2026 attendance + payroll
 * for ALL active employees. Run via browser: http://localhost/SugarlandiaDB/payroll/seed_jan_feb.php
 */
require __DIR__ . '/../config.php';

// ── Deduction helpers ──
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

// Get all active employees with daily rate
$employees = $pdo->query("
    SELECT e.employee_id, e.first_name, e.last_name,
           COALESCE(p.daily_rate, ROUND(p.base_salary / 26, 2)) AS daily_rate
    FROM employees e
    JOIN positions p ON p.position_id = e.position_id
    WHERE e.is_archived = 0
    ORDER BY e.employee_id
")->fetchAll();

if (empty($employees)) {
    die("No active employees found.");
}

// Statuses with weights: Present=60%, Late=15%, Absent=15%, Half-Day=10%
function randomStatus(): string {
    $r = mt_rand(1, 100);
    if ($r <= 60) return 'Present';
    if ($r <= 75) return 'Late';
    if ($r <= 90) return 'Absent';
    return 'Half-Day';
}

function hoursForStatus(string $s): float {
    switch ($s) {
        case 'Present': return 8;
        case 'Late': return mt_rand(5, 7) + (mt_rand(0, 1) ? 0.5 : 0);
        case 'Half-Day': return 4;
        case 'Absent': return 0;
    }
    return 0;
}

// Get working days (Mon-Sat) for a given month
function getWorkingDays(int $year, int $month): array {
    $days = [];
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dow = date('N', strtotime($date)); // 1=Mon, 7=Sun
        if ($dow <= 6) { // Mon-Sat
            $days[] = $date;
        }
    }
    return $days;
}

$months = [
    ['year' => 2026, 'month' => 1, 'label' => 'January 2026'],
    ['year' => 2026, 'month' => 2, 'label' => 'February 2026'],
];

// Prepare statements
$checkAtt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = :eid AND date = :d");
$insertAtt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status, hours_worked) VALUES (:eid, :d, :s, :h)");
$checkPay = $pdo->prepare("SELECT COUNT(*) FROM payroll WHERE employee_id = :eid AND pay_period_start = :ps AND pay_period_end = :pe");
$insertPay = $pdo->prepare("
    INSERT INTO payroll (employee_id, gross_pay, sss_contribution, philhealth_contribution,
        pagibig_contribution, attendance_deduction, total_deductions, net_pay,
        pay_period_start, pay_period_end, payment_date, is_paid, paid_date)
    VALUES (:eid, :gp, :sss, :ph, :pi, 0, :td, :np, :ps, :pe, :pd, 0, NULL)
");

echo "<pre style='font-family:Consolas,monospace; padding:20px;'>";
echo "=== Seeding January & February 2026 ===\n\n";

$pdo->beginTransaction();

try {
    foreach ($months as $m) {
        $workingDays = getWorkingDays($m['year'], $m['month']);
        $periodStart = sprintf('%04d-%02d-01', $m['year'], $m['month']);
        $periodEnd   = date('Y-m-t', strtotime($periodStart));
        $paymentDate = $periodEnd; // Payment at end of month

        echo "── {$m['label']} ({$periodStart} to {$periodEnd}) ──\n";
        echo "   Working days: " . count($workingDays) . "\n\n";

        foreach ($employees as $emp) {
            $eid = $emp['employee_id'];
            $name = $emp['first_name'] . ' ' . $emp['last_name'];
            $dailyRate = (float)$emp['daily_rate'];

            // Insert attendance for each working day
            $presentCount = 0; $lateCount = 0; $absentCount = 0; $halfDayCount = 0;
            $attInserted = 0;

            foreach ($workingDays as $date) {
                // Skip if already exists
                $checkAtt->execute([':eid' => $eid, ':d' => $date]);
                if ($checkAtt->fetchColumn() > 0) continue;

                $status = randomStatus();
                $hours = hoursForStatus($status);

                $insertAtt->execute([
                    ':eid' => $eid,
                    ':d'   => $date,
                    ':s'   => $status,
                    ':h'   => $hours,
                ]);
                $attInserted++;

                switch ($status) {
                    case 'Present': $presentCount++; break;
                    case 'Late': $lateCount++; break;
                    case 'Absent': $absentCount++; break;
                    case 'Half-Day': $halfDayCount++; break;
                }
            }

            // Calculate payroll
            $daysWorked = $presentCount + $lateCount + ($halfDayCount * 0.5);
            $grossPay = round($dailyRate * $daysWorked, 2);
            $sss = computeSSS($grossPay);
            $ph  = computePH($grossPay);
            $pi  = computePI($grossPay);
            $totalDed = round($sss + $ph + $pi, 2);
            $netPay = round($grossPay - $totalDed, 2);

            // Insert payroll if not exists
            $checkPay->execute([':eid' => $eid, ':ps' => $periodStart, ':pe' => $periodEnd]);
            $payExists = $checkPay->fetchColumn() > 0;

            if (!$payExists) {
                $insertPay->execute([
                    ':eid' => $eid,
                    ':gp'  => $grossPay,
                    ':sss' => $sss,
                    ':ph'  => $ph,
                    ':pi'  => $pi,
                    ':td'  => $totalDed,
                    ':np'  => $netPay,
                    ':ps'  => $periodStart,
                    ':pe'  => $periodEnd,
                    ':pd'  => $paymentDate,
                ]);
                echo "   ✅ $name: $attInserted att records | $daysWorked days worked | Gross ₱" . number_format($grossPay, 2) . " | Net ₱" . number_format($netPay, 2) . " | ⏳ Unpaid\n";
            } else {
                echo "   ⏭️ $name: Payroll already exists (att: $attInserted new records added)\n";
            }
        }
        echo "\n";
    }

    $pdo->commit();
    echo "\n=== ✅ Done! All data seeded successfully. ===\n";
    echo "\n<a href='../index.php#section-payroll' style='color:#4f46e5;font-weight:bold;'>→ Go to Dashboard</a>\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
