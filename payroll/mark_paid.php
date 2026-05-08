<?php
/*
 * mark_paid.php
 * AJAX endpoint — marks a payroll record as paid with today's date
 * POST: { payroll_id: X }
 */
require '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']);
    exit;
}

$payroll_id = isset($_POST['payroll_id']) ? (int)$_POST['payroll_id'] : 0;

if ($payroll_id <= 0) {
    echo json_encode(['error' => 'Invalid payroll ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE payroll SET is_paid = 1, paid_date = CURDATE()
        WHERE payroll_id = :id
    ");
    $stmt->execute([':id' => $payroll_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['error' => 'Payroll record not found']);
        exit;
    }

    $paid_date = date('Y-m-d');
    echo json_encode(['success' => true, 'paid_date' => $paid_date]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
?>
