<?php
require '../config.php';

$employee_id  = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
$date         = isset($_POST['date']) ? trim($_POST['date']) : '';
$status       = isset($_POST['status']) ? trim($_POST['status']) : '';
$hours_worked = isset($_POST['hours_worked']) ? (int) $_POST['hours_worked'] : 0;

if ($employee_id <= 0 || empty($date) || empty($status)) {
    die("<p style='color:red;font-family:Arial'>❌ All fields must be filled up. <a href='attendance.php'>Go back</a></p>");
}

// Auto-fix hours based on status
if ($status === 'Present') {
    $hours_worked = 8;
} elseif ($status === 'Half-Day') {
    $hours_worked = 4;
} elseif ($status === 'Absent') {
    $hours_worked = 0;
} elseif ($status === 'Late' && $hours_worked <= 0) {
    die("<p style='color:red;font-family:Arial'>❌ Hours worked must be greater than 0 for Late. <a href='attendance.php'>Go back</a></p>");
}

try {
    $sql = "INSERT INTO attendance (employee_id, date, status, hours_worked)
            VALUES (:employee_id, :date, :status, :hours_worked)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':employee_id'  => $employee_id,
        ':date'         => $date,
        ':status'       => $status,
        ':hours_worked' => $hours_worked,
    ]);

    $newId = $pdo->lastInsertId();

    $extraMsg = '';
    if ($status === 'Late') {
        $month = date('Y-m', strtotime($date));

        $stmt2 = $pdo->prepare("
            SELECT COUNT(*) FROM attendance
            WHERE employee_id = :eid
              AND status = 'Late'
              AND DATE_FORMAT(date, '%Y-%m') = :month
        ");
        $stmt2->execute([
            ':eid'   => $employee_id,
            ':month' => $month
        ]);

        $lateCount = (int) $stmt2->fetchColumn();

        if ($lateCount % 3 === 0) {
            $extraMsg = "&note=" . urlencode("⚠️ Note: Employee has reached {$lateCount} late(s) this month — equivalent to " . ($lateCount / 3) . " absent deduction(s) in payroll.");
        }
    }

    header("Location: ../index.php?success=" . urlencode("Attendance recorded successfully (ID: $newId)") . $extraMsg);
    exit;

} catch (PDOException $e) {
    echo "<p style='color:red;font-family:Arial'>❌ Error: " . htmlspecialchars($e->getMessage()) . " <a href='attendance.php'>Go back</a></p>";
}
?>