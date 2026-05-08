<?php
/*
 * get_employee_salary.php
 * AJAX endpoint — returns employee info + attendance-based gross pay
 * Gross pay = daily_rate × days worked (from attendance records)
 */
require '../config.php';

header('Content-Type: application/json');

$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

if ($employee_id <= 0) {
    echo json_encode(['error' => 'Invalid employee ID']);
    exit;
}

try {
    // Get employee + position
    $stmt = $pdo->prepare("
        SELECT e.employee_id, e.first_name, e.last_name,
               p.position_title, p.base_salary,
               COALESCE(p.daily_rate, ROUND(p.base_salary / 26, 2)) AS daily_rate
        FROM   employees e
        JOIN   positions  p ON p.position_id = e.position_id
        WHERE  e.employee_id = :id
        LIMIT  1
    ");
    $stmt->execute([':id' => $employee_id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        echo json_encode(['error' => 'Employee not found']);
        exit;
    }

    $dailyRate = (float)$emp['daily_rate'];
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
    $absentCount  = (int)($attCounts['Absent'] ?? 0);
    $lateCount    = (int)($attCounts['Late'] ?? 0);
    $halfDayCount = (int)($attCounts['Half-Day'] ?? 0);

    // Calculate days worked:
    // Present = 1 full day, Late = 1 full day, Half-Day = 0.5 day, Absent = 0
    $daysWorked = $presentCount + $lateCount + ($halfDayCount * 0.5);

    // Gross pay = daily_rate × days worked
    $grossPay = round($dailyRate * $daysWorked, 2);

    echo json_encode([
        'employee_id'    => $emp['employee_id'],
        'first_name'     => $emp['first_name'],
        'last_name'      => $emp['last_name'],
        'position_title' => $emp['position_title'],
        'base_salary'    => $emp['base_salary'],
        'daily_rate'     => $dailyRate,
        'hourly_rate'    => round($dailyRate / 8, 2),

        'present_count'  => $presentCount,
        'absent_count'   => $absentCount,
        'late_count'     => $lateCount,
        'half_day_count' => $halfDayCount,
        'days_worked'    => $daysWorked,

        'gross_pay'      => $grossPay,
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
?>