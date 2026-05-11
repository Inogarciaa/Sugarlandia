<?php
session_start();
require '../config.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_count = array_sum($_SESSION['cart']);

// Fetch all products
$products = $pdo->query("SELECT * FROM products ORDER BY product_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sugarlandia Barquillios - Online Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .cart-btn {
            position: relative;
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .cart-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .cart-badge {
            background: var(--secondary);
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.1rem 0.5rem;
            border-radius: 9999px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.1));
            padding: 4rem 5%;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .hero p {
            color: var(--gray);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Product Grid */
        .container {
            padding: 4rem 5%;
            flex-grow: 1;
        }

        .section-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            height: 200px;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .product-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .product-variant {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .product-stock {
            font-size: 0.85rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .stock-in { color: var(--success); }
        .stock-out { color: var(--danger); }

        .btn-add {
            margin-top: auto;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-add:hover:not(:disabled) {
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            transform: scale(1.02);
        }

        .btn-add:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            color: #64748b;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transform: translateY(150%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
        }

        .toast.show {
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">Sugarlandia</a>
        <a href="cart.php" class="cart-btn">
            🛒 Cart <span class="cart-badge" id="cart-count"><?= $cart_count ?></span>
        </a>
    </nav>

    <div class="hero">
        <h1>Welcome to Sugarlandia!</h1>
        <p>Discover our delicious, handcrafted barquillios. Reserve your favorites online and pick them up fresh.</p>
    </div>

    <div class="container">
        <h2 class="section-title">Our Products</h2>
        <div class="product-grid">
            <?php foreach ($products as $p): 
                $in_stock = $p['stock_quantity'] > 0;
            ?>
            <div class="product-card">
                <div class="product-image">
                    🍪
                </div>
                <div class="product-content">
                    <h3 class="product-name"><?= htmlspecialchars($p['product_name']) ?></h3>
                    <div class="product-variant"><?= htmlspecialchars($p['product_variant']) ?></div>
                    <div class="product-price">₱<?= number_format($p['price'], 2) ?></div>
                    
                    <div class="product-stock <?= $in_stock ? 'stock-in' : 'stock-out' ?>">
                        <?= $in_stock ? $p['stock_quantity'] . ' in stock' : 'Out of Stock' ?>
                    </div>

                    <button class="btn-add" 
                            onclick="addToCart(<?= $p['product_id'] ?>)" 
                            <?= !$in_stock ? 'disabled' : '' ?>>
                        <?= $in_stock ? 'Add to Cart' : 'Unavailable' ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="toast" id="toast">
        <span style="color: var(--success); font-size: 1.25rem;">✅</span>
        <span id="toast-msg">Added to cart!</span>
    </div>

    <script>
        function addToCart(productId) {
            fetch('ajax_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=add&product_id=' + productId
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('cart-count').textContent = data.cart_count;
                    showToast('Item added to cart!');
                } else {
                    showToast(data.error || 'Could not add item.', true);
                }
            });
        }

        function showToast(msg, isError = false) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-msg').textContent = msg;
            toast.style.borderLeft = isError ? '4px solid var(--danger)' : '4px solid var(--success)';
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>
