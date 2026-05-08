<?php
/*
 * get_attendance_summary.php
 * AJAX endpoint — returns employee info + current month attendance counts
 * Called by attendance.php when user types an Employee ID
 */
require '../config.php';

header('Content-Type: application/json');

$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

if ($employee_id <= 0) {
    echo json_encode(['error' => 'Invalid employee ID']);
    exit;
}

try {
    // Get employee + position info
    $stmt = $pdo->prepare("
        SELECT e.employee_id, e.first_name, e.last_name,
               p.position_title, p.base_salary
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

    $currentMonth = date('Y-m');

    // Count absences this month
    $stmt2 = $pdo->prepare("
        SELECT COUNT(*) FROM attendance
        WHERE  employee_id = :eid
          AND  status      = 'Absent'
          AND  DATE_FORMAT(date, '%Y-%m') = :month
    ");
    $stmt2->execute([':eid' => $employee_id, ':month' => $currentMonth]);
    $absentCount = (int)$stmt2->fetchColumn();

    // Count lates this month
    $stmt3 = $pdo->prepare("
        SELECT COUNT(*) FROM attendance
        WHERE  employee_id = :eid
          AND  status      = 'Late'
          AND  DATE_FORMAT(date, '%Y-%m') = :month
    ");
    $stmt3->execute([':eid' => $employee_id, ':month' => $currentMonth]);
    $lateCount = (int)$stmt3->fetchColumn();

    echo json_encode([
        'employee_id'    => $emp['employee_id'],
        'first_name'     => $emp['first_name'],
        'last_name'      => $emp['last_name'],
        'position_title' => $emp['position_title'],
        'base_salary'    => $emp['base_salary'],
        'absent_count'   => $absentCount,
        'late_count'     => $lateCount,
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
?>