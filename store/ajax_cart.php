<?php
session_start();
require '../config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$product_id = (int)($_POST['product_id'] ?? 0);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($action === 'add' && $product_id > 0) {
    // Check stock
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $stock = $stmt->fetchColumn();

    if ($stock !== false) {
        $current_qty = $_SESSION['cart'][$product_id] ?? 0;
        if ($current_qty + 1 > $stock) {
            echo json_encode(['success' => false, 'error' => 'Not enough stock available.']);
            exit;
        }

        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]++;
        } else {
            $_SESSION['cart'][$product_id] = 1;
        }
        $count = array_sum($_SESSION['cart']);
        echo json_encode(['success' => true, 'cart_count' => $count]);
        exit;
    }
}

if ($action === 'remove' && $product_id > 0) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update' && $product_id > 0) {
    $qty = (int)($_POST['quantity'] ?? 0);
    if ($qty > 0) {
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $stock = $stmt->fetchColumn();
        
        if ($qty > $stock) {
            echo json_encode(['success' => false, 'error' => 'Exceeds available stock.']);
            exit;
        }
        $_SESSION['cart'][$product_id] = $qty;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
