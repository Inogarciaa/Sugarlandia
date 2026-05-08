<?php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name    = trim($_POST['product_name']);
    $product_variant = trim($_POST['product_variant']);
    $price           = (int)$_POST['price'];
    $stock_quantity  = (int)$_POST['stock_quantity'];

    if (empty($product_name) || empty($product_variant) || $price < 0 || $stock_quantity < 0) {
        header("Location: products.php?error=" . urlencode("All fields must be filled up correctly."));
        exit;
    }

    if (!isset($_FILES['image'])) {
        header("Location: products.php?error=" . urlencode("Image field missing."));
        exit;
    }

    $file       = $_FILES['image'];
    $customName = $_POST['custom_name'] ?? null;

    // check upload error
    if ($file['error'] !== 0) {
        header("Location: products.php?error=" . urlencode("Upload error."));
        exit;
    }

    // validate image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        header("Location: products.php?error=" . urlencode("Invalid image."));
        exit;
    }

    // validate mime type
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($check['mime'], $allowed)) {
        header("Location: products.php?error=" . urlencode("Unsupported format. Only JPEG, PNG, GIF, and WEBP are allowed."));
        exit;
    }

    // validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        header("Location: products.php?error=" . urlencode("File too large (max 5MB)."));
        exit;
    }

    // ADDED: validate pixel dimensions (max 1920x1080)
    $imgWidth  = $check[0];
    $imgHeight = $check[1];
    if ($imgWidth > 1920 || $imgHeight > 1080) {
        header("Location: products.php?error=" . urlencode("Image too large. Max allowed is 1920×1080px. Your image is {$imgWidth}×{$imgHeight}px."));
        exit;
    }

    // build filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if ($customName) {
        $safe     = preg_replace("/[^a-zA-Z0-9_-]/", "_", $customName);
        $fileName = $safe . "_" . uniqid() . "." . $ext;
    } else {
        $fileName = uniqid() . "." . $ext;
    }

    $path = "../uploads/" . $fileName;

    try {
        if (move_uploaded_file($file['tmp_name'], $path)) {
            $sql  = "INSERT INTO products (product_name, product_variant, price, stock_quantity, image)
                     VALUES (:product_name, :product_variant, :price, :stock_quantity, :image)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':product_name'    => $product_name,
                ':product_variant' => $product_variant,
                ':price'           => $price,
                ':stock_quantity'  => $stock_quantity,
                ':image'           => $fileName
            ]);

            $newId = $pdo->lastInsertId();
            header("Location: products.php?success=" . urlencode("Product saved successfully (ID: $newId)"));
            exit;
        } else {
            header("Location: products.php?error=" . urlencode("Upload failed. Please try again."));
            exit;
        }
    } catch (PDOException $e) {
        header("Location: products.php?error=" . urlencode("DB Error: " . $e->getMessage()));
        exit;
    }
}
?>