<?php
session_start();
require '../config.php';

if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$cart_count = array_sum($_SESSION['cart']);

// Calculate total
$total = 0;
$ids = implode(',', array_keys($_SESSION['cart']));
$stmt = $pdo->query("SELECT * FROM products WHERE product_id IN ($ids)");
$cart_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($cart_products as $row) {
    $qty = $_SESSION['cart'][$row['product_id']];
    $total += $qty * $row['price'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $date = trim($_POST['pickup_date'] ?? '');

    if (!$name || !$phone || !$date) {
        $error = 'Name, Phone, and Pickup Date are required.';
    } else {
        try {
            $pdo->beginTransaction();

            // Create Reservation
            $stmt = $pdo->prepare("INSERT INTO reservations (customer_name, customer_phone, customer_email, pickup_date, total_amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $email, $date, $total]);
            $res_id = $pdo->lastInsertId();

            // Insert Items and Update Stock
            $item_stmt = $pdo->prepare("INSERT INTO reservation_items (reservation_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stock_stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");

            foreach ($cart_products as $row) {
                $pid = $row['product_id'];
                $qty = $_SESSION['cart'][$pid];
                
                // Double check stock
                $check_stock = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ? FOR UPDATE");
                $check_stock->execute([$pid]);
                $curr_stock = $check_stock->fetchColumn();

                if ($curr_stock < $qty) {
                    throw new Exception("Not enough stock for " . $row['product_name']);
                }

                $item_stmt->execute([$res_id, $pid, $qty, $row['price']]);
                $stock_stmt->execute([$qty, $pid]);
            }

            $pdo->commit();
            $_SESSION['cart'] = []; // clear cart
            
            header("Location: success.php?id=" . $res_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sugarlandia Barquillios</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Shared */
        :root { --primary: #6366f1; --primary-dark: #4f46e5; --secondary: #ec4899; --dark: #0f172a; --light: #f8fafc; --gray: #64748b; --danger: #ef4444; --success: #10b981; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light); color: var(--dark); }
        .navbar { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        
        .container { max-width: 800px; margin: 3rem auto; padding: 0 1.5rem; }
        .checkout-box { background: white; border-radius: 16px; padding: 2.5rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        
        .page-title { font-size: 2rem; margin-bottom: 1.5rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: #334155; }
        .form-control { width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem; transition: border-color 0.2s; outline: none; font-family: inherit; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        
        .order-summary { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-top: 2rem; }
        .order-summary h3 { margin-bottom: 1rem; font-size: 1.2rem; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--gray); font-size: 0.95rem; }
        .summary-total { display: flex; justify-content: space-between; font-weight: 700; font-size: 1.25rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #cbd5e1; color: var(--dark); }
        
        .submit-btn { width: 100%; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; border: none; padding: 1rem; border-radius: 8px; font-weight: 600; font-size: 1.1rem; cursor: pointer; transition: all 0.3s; margin-top: 2rem; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(99,102,241,0.4); }
        
        .error-msg { background: #fef2f2; border-left: 4px solid var(--danger); padding: 1rem; border-radius: 4px; color: #991b1b; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">Sugarlandia</a>
        <a href="cart.php" style="color:var(--dark); text-decoration:none; font-weight:500;">← Back to Cart</a>
    </nav>

    <div class="container">
        <div class="checkout-box">
            <h1 class="page-title">Checkout Details</h1>
            
            <?php if($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="Juan Dela Cruz">
                </div>
                
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" required placeholder="09123456789">
                </div>
                
                <div class="form-group">
                    <label>Email Address (Optional)</label>
                    <input type="email" name="email" class="form-control" placeholder="juan@example.com">
                </div>
                
                <div class="form-group">
                    <label>Preferred Pick-up Date *</label>
                    <input type="date" name="pickup_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>

                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <?php foreach($cart_products as $row): 
                        $qty = $_SESSION['cart'][$row['product_id']];
                    ?>
                        <div class="summary-item">
                            <span><?= $qty ?>x <?= htmlspecialchars($row['product_name']) ?></span>
                            <span>₱<?= number_format($row['price'] * $qty, 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-total">
                        <span>Total to Pay</span>
                        <span>₱<?= number_format($total, 2) ?></span>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Confirm Reservation</button>
            </form>
        </div>
    </div>

</body>
</html>
