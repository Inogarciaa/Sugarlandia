<?php
/*
 * month_status.php
 * AJAX endpoint — returns payroll status summary for a given month
 * GET: ?month=YYYY-MM
 */
require '../config.php';

header('Content-Type: application/json');

$month = isset($_GET['month']) ? trim($_GET['month']) : '';

if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    echo json_encode(['error' => 'Invalid month format']);
    exit;
}

$periodStart = $month . '-01';
$periodEnd   = date('Y-m-t', strtotime($periodStart));

try {
    // Get summary counts
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN is_paid = 0 OR is_paid IS NULL THEN 1 ELSE 0 END) as unpaid_count
        FROM payroll 
        WHERE pay_period_start = :ps AND pay_period_end = :pe
    ");
    $stmt->execute([':ps' => $periodStart, ':pe' => $periodEnd]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get per-employee details
    $detailStmt = $pdo->prepare("
        SELECT CONCAT(e.first_name, ' ', e.last_name) as name,
               p.is_paid, p.paid_date, p.gross_pay, p.net_pay, p.payroll_id
        FROM payroll p
        JOIN employees e ON p.employee_id = e.employee_id
        WHERE p.pay_period_start = :ps AND p.pay_period_end = :pe
        ORDER BY p.is_paid ASC, e.first_name ASC
    ");
    $detailStmt->execute([':ps' => $periodStart, ':pe' => $periodEnd]);
    $employees = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'month'        => $month,
        'total'        => (int)$summary['total'],
        'paid_count'   => (int)$summary['paid_count'],
        'unpaid_count' => (int)$summary['unpaid_count'],
        'employees'    => $employees,
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
?>
