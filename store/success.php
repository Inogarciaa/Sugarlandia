<?php
session_start();
require '../config.php';

$res_id = $_GET['id'] ?? 0;

if (!$res_id) {
    header("Location: index.php");
    exit;
}

// Fetch reservation details
$stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
$stmt->execute([$res_id]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$res) {
    echo "Reservation not found.";
    exit;
}

// Fetch items
$item_stmt = $pdo->prepare("
    SELECT ri.*, p.product_name, p.product_variant 
    FROM reservation_items ri
    JOIN products p ON ri.product_id = p.product_id
    WHERE ri.reservation_id = ?
");
$item_stmt->execute([$res_id]);
$items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);

$formatted_id = str_pad((string)$res_id, 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Receipt - Sugarlandia Barquillios</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        :root { --primary: #6366f1; --dark: #0f172a; --light: #f8fafc; --success: #10b981; --gray: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light); color: var(--dark); display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 1rem; }
        
        .receipt-container {
            background: white;
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
            margin-bottom: 2rem;
            border-top: 8px solid var(--primary);
        }

        .receipt-header {
            text-align: center;
            padding: 2rem 1.5rem 1.5rem;
            border-bottom: 2px dashed #e2e8f0;
        }

        .receipt-logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .receipt-title {
            font-size: 1rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .qr-placeholder {
            width: 100px;
            height: 100px;
            background: #f1f5f9;
            margin: 0 auto;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .receipt-body {
            padding: 1.5rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        .info-label { color: var(--gray); }
        .info-val { font-weight: 600; text-align: right; }

        .items-table {
            width: 100%;
            margin: 1.5rem 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            border-collapse: collapse;
        }
        .items-table th { text-align: left; padding: 0.75rem 0; color: var(--gray); font-size: 0.85rem; font-weight: 500; border-bottom: 1px solid #e2e8f0; }
        .items-table td { padding: 0.75rem 0; font-size: 0.9rem; vertical-align: top; }
        .items-table .price-col { text-align: right; }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        .total-label { font-size: 1.1rem; font-weight: 600; }
        .total-val { font-size: 1.5rem; font-weight: 700; color: var(--primary); }

        .receipt-footer {
            background: #f8fafc;
            text-align: center;
            padding: 1rem;
            font-size: 0.85rem;
            color: var(--gray);
            border-top: 2px dashed #e2e8f0;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            color: var(--dark);
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn:hover { background: #f1f5f9; }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #4f46e5);
            color: white;
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
            background: linear-gradient(135deg, var(--primary), #4f46e5);
        }
    </style>
</head>
<body>

    <!-- The Receipt Element to capture -->
    <div class="receipt-container" id="receipt">
        <div class="receipt-header">
            <div class="receipt-logo">Sugarlandia Barquillios</div>
            <div class="receipt-title">Reservation Receipt</div>
            <div class="qr-placeholder">📦</div>
            <div style="margin-top: 1rem; font-weight: 700; font-size: 1.25rem;">ID: #<?= $formatted_id ?></div>
        </div>

        <div class="receipt-body">
            <div class="info-row">
                <span class="info-label">Customer</span>
                <span class="info-val"><?= htmlspecialchars($res['customer_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone</span>
                <span class="info-val"><?= htmlspecialchars($res['customer_phone']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Pickup Date</span>
                <span class="info-val"><?= htmlspecialchars($res['pickup_date']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="info-val" style="color:var(--primary);"><?= htmlspecialchars($res['status']) ?></span>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="price-col">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div style="color:var(--gray);font-size:0.8rem;"><?= $item['quantity'] ?>x (<?= htmlspecialchars($item['product_variant']) ?>)</div>
                        </td>
                        <td class="price-col">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-row">
                <span class="total-label">Total Amount</span>
                <span class="total-val">₱<?= number_format($res['total_amount'], 2) ?></span>
            </div>
        </div>

        <div class="receipt-footer">
            Please present this receipt/image when picking up your items. Thank you!
        </div>
    </div>

    <!-- Action Buttons (Won't be in the downloaded image) -->
    <div class="action-buttons">
        <button class="btn btn-primary" onclick="downloadReceipt()">
            ⬇️ Download Image
        </button>
        <a href="index.php" class="btn">Return to Store</a>
    </div>

    <script>
        function downloadReceipt() {
            const receipt = document.getElementById('receipt');
            const btn = document.querySelector('.btn-primary');
            
            // Visual feedback
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Generating...';
            
            // Ensure fonts and styles are fully loaded before capturing
            setTimeout(() => {
                html2canvas(receipt, {
                    scale: 2, // Higher quality
                    backgroundColor: "#ffffff",
                    logging: false
                }).then(canvas => {
                    // Create an image and download it
                    const link = document.createElement('a');
                    link.download = 'Sugarlandia_Receipt_#<?= $formatted_id ?>.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                    
                    // Reset button
                    btn.innerHTML = '✅ Downloaded!';
                    setTimeout(() => { btn.innerHTML = originalText; }, 2000);
                });
            }, 100);
        }
    </script>
</body>
</html>
