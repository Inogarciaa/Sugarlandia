<?php
include "config.php";

if (isset($_FILES['image'])) {

    $file       = $_FILES['image'];
    $customName = $_POST['custom_name'] ?? null;

    // check upload error
    if ($file['error'] !== 0) {
        header("Location: image.php?error=" . urlencode("Upload error."));
        exit;
    }

    // validate image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        header("Location: image.php?error=" . urlencode("Invalid image."));
        exit;
    }

    // validate mime type
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($check['mime'], $allowed)) {
        header("Location: image.php?error=" . urlencode("Unsupported format. Only JPEG, PNG, GIF, and WEBP are allowed."));
        exit;
    }

    // validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        header("Location: image.php?error=" . urlencode("File too large (max 5MB)."));
        exit;
    }

    // validate pixel dimensions (max 1920x1080)
    $imgWidth  = $check[0];
    $imgHeight = $check[1];
    if ($imgWidth > 1920 || $imgHeight > 1080) {
        header("Location: image.php?error=" . urlencode("Image too large. Max allowed is 1920x1080px. Your image is {$imgWidth}x{$imgHeight}px."));
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

    $path = "uploads/" . $fileName;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        $stmt = $pdo->prepare("INSERT INTO tbl_files (name) VALUES (:name)");
        $stmt->execute(['name' => $fileName]);
        header("Location: image.php?success=1");
        exit;
    } else {
        header("Location: image.php?error=" . urlencode("Upload failed. Please try again."));
        exit;
    }

} else {
    // if accessed directly with no file
    header("Location: image.php");
    exit;
}
?>