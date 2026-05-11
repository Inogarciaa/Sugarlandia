<?php
/*
 * get_payslip.php
 * AJAX endpoint — returns payslip data with day-by-day salary breakdown
 * 
 * Accepts either:
 *   ?payroll_id=X        — load from a saved payroll record
 *   ?employee_id=X&month=YYYY-MM  — calculate live from attendance
 */
require '../config.php';

header('Content-Type: application/json');

$payroll_id  = isset($_GET['payroll_id']) ? (int)$_GET['payroll_id'] : 0;
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$month       = isset($_GET['month']) ? trim($_GET['month']) : '';

// ── MODE 1: Load from saved payroll record ──
if ($payroll_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.payroll_id, p.employee_id, p.gross_pay, p.net_pay,
                   p.sss_contribution, p.philhealth_contribution, p.pagibig_contribution,
                   p.attendance_deduction, p.total_deductions, p.payment_date,
                   p.pay_period_start, p.pay_period_end,
                   p.is_paid, p.paid_date,
                   e.first_name, e.last_name, e.profile_picture, e.employee_id,
                   pos.position_title, pos.base_salary, pos.daily_rate
            FROM payroll p
            JOIN employees e ON p.employee_id = e.employee_id
            JOIN positions pos ON e.position_id = pos.position_id
            WHERE p.payroll_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $payroll_id]);
        $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payroll) {
            echo json_encode(['error' => 'Payroll record not found']);
            exit;
        }

        $employee_id = $payroll['employee_id'];
        $payDate = $payroll['payment_date'];
        $ps = $payroll['pay_period_start'];
        $pe = $payroll['pay_period_end'];

        $validStart = !empty($ps) && $ps !== '0000-00-00' && strtotime($ps) !== false;
        $validEnd   = !empty($pe) && $pe !== '0000-00-00' && strtotime($pe) !== false;

        $periodStart = $validStart ? $ps : date('Y-m-01', strtotime($payDate));
        $periodEnd   = $validEnd   ? $pe : date('Y-m-t', strtotime($payDate));

        $dailyRate = !empty($payroll['daily_rate']) && $payroll['daily_rate'] > 0
            ? (float)$payroll['daily_rate']
            : round((float)$payroll['base_salary'] / 26, 2);

        $savedPay = [
            'gross_pay'       => (float)$payroll['gross_pay'],
            'sss'             => (float)$payroll['sss_contribution'],
            'philhealth'      => (float)$payroll['philhealth_contribution'],
            'pagibig'         => (float)$payroll['pagibig_contribution'],
            'attendance_ded'  => (float)$payroll['attendance_deduction'],
            'total_deductions'=> (float)$payroll['total_deductions'],
            'net_pay'         => (float)$payroll['net_pay'],
            'payment_date'    => $payDate,
            'payroll_id'      => $payroll['payroll_id'],
            'is_saved'        => true,
            'is_paid'         => (int)$payroll['is_paid'],
            'paid_date'       => $payroll['paid_date'],
        ];

        $empName    = $payroll['first_name'] . ' ' . $payroll['last_name'];
        $empPic     = $payroll['profile_picture'];
        $empPos     = $payroll['position_title'];

    } catch (PDOException $e) {
        echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
        exit;
    }
}
// ── MODE 2: Live calculation from employee + month ──
elseif ($employee_id > 0 && !empty($month)) {
    try {
        $stmt = $pdo->prepare("
            SELECT e.employee_id, e.first_name, e.last_name, e.profile_picture,
                   pos.position_title, pos.base_salary, pos.daily_rate
            FROM employees e
            JOIN positions pos ON e.position_id = pos.position_id
            WHERE e.employee_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $employee_id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            echo json_encode(['error' => 'Employee not found']);
            exit;
        }

        $periodStart = $month . '-01';
        $periodEnd   = date('Y-m-t', strtotime($periodStart));

        $dailyRate = !empty($emp['daily_rate']) && $emp['daily_rate'] > 0
            ? (float)$emp['daily_rate']
            : round((float)$emp['base_salary'] / 26, 2);

        $empName = $emp['first_name'] . ' ' . $emp['last_name'];
        $empPic  = $emp['profile_picture'];
        $empPos  = $emp['position_title'];

        // Check if a saved payroll record exists for this employee+month
        $savedPay = null;
        $payCheck = $pdo->prepare("
            SELECT payroll_id, gross_pay, net_pay,
                   sss_contribution, philhealth_contribution, pagibig_contribution,
                   attendance_deduction, total_deductions, payment_date,
                   is_paid, paid_date
            FROM payroll
            WHERE employee_id = :eid
              AND pay_period_start = :ps AND pay_period_end = :pe
            LIMIT 1
        ");
        $payCheck->execute([':eid' => $employee_id, ':ps' => $periodStart, ':pe' => $periodEnd]);
        $existingPay = $payCheck->fetch(PDO::FETCH_ASSOC);

        if ($existingPay) {
            $savedPay = [
                'gross_pay'       => (float)$existingPay['gross_pay'],
                'sss'             => (float)$existingPay['sss_contribution'],
                'philhealth'      => (float)$existingPay['philhealth_contribution'],
                'pagibig'         => (float)$existingPay['pagibig_contribution'],
                'attendance_ded'  => (float)$existingPay['attendance_deduction'],
                'total_deductions'=> (float)$existingPay['total_deductions'],
                'net_pay'         => (float)$existingPay['net_pay'],
                'payment_date'    => $existingPay['payment_date'],
                'payroll_id'      => $existingPay['payroll_id'],
                'is_saved'        => true,
                'is_paid'         => (int)$existingPay['is_paid'],
                'paid_date'       => $existingPay['paid_date'],
            ];
        }
        // If no saved record, $savedPay stays null → will compute live

    } catch (PDOException $e) {
        echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['error' => 'Missing parameters. Provide payroll_id or employee_id+month.']);
    exit;
}

// ── Get attendance + build breakdown ──
try {
    $hourlyRate = round($dailyRate / 8, 2);

    $attStmt = $pdo->prepare("
        SELECT date, status, hours_worked
        FROM attendance
        WHERE employee_id = :eid
          AND date BETWEEN :start AND :end
        ORDER BY date ASC
    ");
    $attStmt->execute([':eid' => $employee_id, ':start' => $periodStart, ':end' => $periodEnd]);
    $attendanceRecords = $attStmt->fetchAll(PDO::FETCH_ASSOC);

    $dailyBreakdown = [];
    $totalDaysWorked = 0; $totalHoursWorked = 0; $totalEarnings = 0;
    $presentDays = 0; $absentDays = 0; $lateDays = 0; $halfDays = 0;

    foreach ($attendanceRecords as $att) {
        $hours = (float)$att['hours_worked'];
        $status = $att['status'];
        $daySalary = 0;

        if ($status === 'Present')  { $daySalary = $dailyRate; $presentDays++; $totalDaysWorked++; }
        elseif ($status === 'Late') { $daySalary = $dailyRate; $lateDays++; $totalDaysWorked++; }
        elseif ($status === 'Half-Day') { $daySalary = $dailyRate / 2; $halfDays++; $totalDaysWorked += 0.5; }
        elseif ($status === 'Absent') { $daySalary = 0; $absentDays++; }

        $totalHoursWorked += $hours;
        $totalEarnings += $daySalary;

        $dailyBreakdown[] = [
            'date'       => $att['date'],
            'status'     => $status,
            'hours'      => $hours,
            'daily_rate' => $dailyRate,
            'earned'     => round($daySalary, 2),
        ];
    }

    // Get available months for this employee (for the dropdown)
    $monthStmt = $pdo->prepare("
        SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') AS m
        FROM attendance
        WHERE employee_id = :eid
        ORDER BY m DESC
    ");
    $monthStmt->execute([':eid' => $employee_id]);
    $availableMonths = array_column($monthStmt->fetchAll(PDO::FETCH_ASSOC), 'm');

    // If live mode, compute pay
    if ($savedPay === null) {
        $grossPay = round($totalEarnings, 2);

        // SSS
        $sssTable = [
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
        $sss = 1350;
        foreach ($sssTable as $row) { if ($grossPay >= $row[0] && $grossPay <= $row[1]) { $sss = $row[2]; break; } }

        $phil = max(500, min(2500, round($grossPay * 0.025, 2)));
        $pag  = min(100, round($grossPay * ($grossPay <= 1500 ? 0.01 : 0.02), 2));
        $totalDed = $sss + $phil + $pag;
        $netPay   = round($grossPay - $totalDed, 2);

        $savedPay = [
            'gross_pay'       => $grossPay,
            'sss'             => $sss,
            'philhealth'      => $phil,
            'pagibig'         => $pag,
            'attendance_ded'  => 0,
            'total_deductions'=> $totalDed,
            'net_pay'         => $netPay,
            'payment_date'    => null,
            'payroll_id'      => null,
            'is_saved'        => false,
        ];
    }

    $selectedMonth = substr($periodStart, 0, 7);

    echo json_encode(array_merge($savedPay, [
        'employee_id'        => $employee_id,
        'employee_name'      => $empName,
        'profile_picture'    => $empPic,
        'position'           => $empPos,
        'daily_rate'         => $dailyRate,
        'hourly_rate'        => $hourlyRate,
        'hours_per_day'      => 8,
        'period_start'       => $periodStart,
        'period_end'         => $periodEnd,
        'selected_month'     => $selectedMonth,
        'available_months'   => $availableMonths,
        'present_days'       => $presentDays,
        'absent_days'        => $absentDays,
        'late_days'          => $lateDays,
        'half_days'          => $halfDays,
        'total_days_worked'  => $totalDaysWorked,
        'total_hours_worked' => $totalHoursWorked,
        'total_earnings'     => round($totalEarnings, 2),
        'daily_breakdown'    => $dailyBreakdown,
    ]));

} catch (PDOException $e) {
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
?>
