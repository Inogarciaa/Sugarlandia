<?php
session_start();
require '../config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart_count = array_sum($_SESSION['cart']);

// Fetch cart items
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $stmt = $pdo->query("SELECT * FROM products WHERE product_id IN ($ids)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $qty = $_SESSION['cart'][$row['product_id']];
        
        // Ensure cart qty does not exceed stock in case it changed
        if ($qty > $row['stock_quantity']) {
            $qty = max(0, $row['stock_quantity']);
            $_SESSION['cart'][$row['product_id']] = $qty;
            if($qty == 0) unset($_SESSION['cart'][$row['product_id']]);
        }
        
        if ($qty > 0) {
            $row['cart_qty'] = $qty;
            $row['subtotal'] = $qty * $row['price'];
            $total += $row['subtotal'];
            $cart_items[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Sugarlandia Barquillios</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Shared Styles */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #ec4899;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;
            --danger: #ef4444;
            --success: #10b981;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light); color: var(--dark); }
        .navbar { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        .cart-btn { background: #fff; border: 1px solid #e2e8f0; padding: 0.5rem 1rem; border-radius: 9999px; text-decoration: none; color: var(--dark); font-weight: 500; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .cart-badge { background: var(--secondary); color: white; font-size: 0.75rem; font-weight: 700; padding: 0.1rem 0.5rem; border-radius: 9999px; }

        /* Cart specific */
        .container { max-width: 900px; margin: 3rem auto; padding: 0 1.5rem; }
        .page-title { font-size: 2rem; margin-bottom: 2rem; }
        
        .cart-box { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .cart-item { display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem 0; border-bottom: 1px solid #e2e8f0; }
        .cart-item:last-child { border-bottom: none; }
        
        .item-img { width: 80px; height: 80px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
        .item-info { flex-grow: 1; }
        .item-name { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.25rem; }
        .item-variant { font-size: 0.85rem; color: var(--gray); }
        .item-price { font-weight: 600; color: var(--primary); }
        
        .qty-controls { display: flex; align-items: center; gap: 0.5rem; }
        .qty-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #cbd5e1; background: white; cursor: pointer; font-weight: bold; transition: all 0.2s; }
        .qty-btn:hover { background: #f1f5f9; }
        .qty-input { width: 50px; text-align: center; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.25rem; font-family: inherit; }
        
        .item-subtotal { font-weight: 700; width: 100px; text-align: right; }
        .btn-remove { background: none; border: none; color: var(--danger); cursor: pointer; padding: 0.5rem; font-size: 1.2rem; transition: transform 0.2s; }
        .btn-remove:hover { transform: scale(1.1); }

        .cart-summary { margin-top: 2rem; border-top: 2px dashed #e2e8f0; padding-top: 2rem; display: flex; justify-content: space-between; align-items: flex-end; }
        .total-label { font-size: 1.2rem; color: var(--gray); }
        .total-amount { font-size: 2.5rem; font-weight: 700; color: var(--dark); }
        
        .checkout-btn { display: inline-block; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; text-decoration: none; padding: 1rem 3rem; border-radius: 8px; font-weight: 600; font-size: 1.1rem; transition: all 0.3s; box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .checkout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(99,102,241,0.4); }
        
        .empty-cart { text-align: center; padding: 3rem; color: var(--gray); }
        .empty-cart a { color: var(--primary); font-weight: 600; text-decoration: none; margin-top: 1rem; display: inline-block; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">Sugarlandia</a>
        <a href="cart.php" class="cart-btn">
            🛒 Cart <span class="cart-badge" id="cart-count"><?= $cart_count ?></span>
        </a>
    </nav>

    <div class="container">
        <h1 class="page-title">Your Cart</h1>
        
        <div class="cart-box">
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <div style="font-size:4rem;margin-bottom:1rem;">🛒</div>
                    <h2>Your cart is empty</h2>
                    <a href="index.php">← Continue Shopping</a>
                </div>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item" id="item-<?= $item['product_id'] ?>">
                    <div class="item-img">🍪</div>
                    <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div class="item-variant"><?= htmlspecialchars($item['product_variant']) ?></div>
                        <div class="item-price">₱<?= number_format($item['price'], 2) ?></div>
                    </div>
                    
                    <div class="qty-controls">
                        <button class="qty-btn" onclick="updateQty(<?= $item['product_id'] ?>, -1)">-</button>
                        <input type="number" class="qty-input" id="qty-<?= $item['product_id'] ?>" value="<?= $item['cart_qty'] ?>" readonly>
                        <button class="qty-btn" onclick="updateQty(<?= $item['product_id'] ?>, 1)">+</button>
                    </div>
                    
                    <div class="item-subtotal">₱<span id="sub-<?= $item['product_id'] ?>"><?= number_format($item['subtotal'], 2) ?></span></div>
                    
                    <button class="btn-remove" onclick="removeItem(<?= $item['product_id'] ?>)">🗑️</button>
                </div>
                <?php endforeach; ?>
                
                <div class="cart-summary">
                    <div>
                        <a href="index.php" style="color:var(--gray);text-decoration:none;font-weight:500;">← Continue Shopping</a>
                    </div>
                    <div style="text-align:right;">
                        <div class="total-label">Total Amount</div>
                        <div class="total-amount">₱<span id="cart-total"><?= number_format($total, 2) ?></span></div>
                        <br>
                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout →</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateQty(id, change) {
            const input = document.getElementById('qty-' + id);
            let newVal = parseInt(input.value) + change;
            if (newVal < 1) newVal = 1;
            
            fetch('ajax_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=update&product_id=' + id + '&quantity=' + newVal
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload(); // Quickest way to update totals accurately
                } else {
                    alert(data.error || 'Could not update quantity.');
                }
            });
        }

        function removeItem(id) {
            if(!confirm('Remove this item?')) return;
            fetch('ajax_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=remove&product_id=' + id
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
            });
        }
    </script>
</body>
</html>
