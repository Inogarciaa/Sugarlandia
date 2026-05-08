<?php include "config.php"; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Upload System</title>
</head>
<style>
   body {
    font-family: Arial;
    margin: 0;
    padding: 20px;
    background: #f4f4f4;
}

.container {
    max-width: 600px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

input, button {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    box-sizing: border-box;
}

.gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.card {
    background: #fafafa;
    border-radius: 10px;
    padding: 10px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.card img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
}

.card p {
    font-size: 12px;
    margin-top: 8px;
    word-break: break-word;
    color: #333;
}

#preview img {
    margin-top: 10px;
    width: 120px;
    border-radius: 8px;
}

.alert {
    padding: 10px 14px;
    border-radius: 8px;
    margin-top: 12px;
    font-size: 14px;
}
.alert-error   { background: #ffe5e5; color: #c0392b; border: 1px solid #f5c6cb; }
.alert-success { background: #e5ffed; color: #1e8449; border: 1px solid #b7dfb8; }
.dim-note { font-size: 12px; color: #888; margin-top: 6px; }
</style>
<body>

<div class="container">
    <h2>Upload Image</h2>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Image uploaded successfully!</div>
    <?php endif; ?>

    <form action="upload.php" method="POST" enctype="multipart/form-data">

        <input type="file" name="image" id="imageInput" accept="image/*" required>
        <p class="dim-note">Max size: 5MB &nbsp;|&nbsp; Max dimensions: 1920×1080px &nbsp;|&nbsp; Formats: JPEG, PNG, GIF, WEBP</p>

        <input type="text" name="custom_name" placeholder="Custom file name (optional)">

        <div id="message"></div>
        <div id="preview"></div>

        <button type="submit">Upload</button>

    </form>
</div>

<div class="container">
    <h2>Uploaded Images</h2>

    <div class="gallery">
    <?php
    $stmt   = $pdo->query("SELECT * FROM tbl_files ORDER BY id DESC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($images as $row) {
        $file = "uploads/" . htmlspecialchars($row['name']);
        echo "<div class='card'>";
        if (file_exists($file)) {
            echo "<img src='$file'>";
        }
        echo "<p>" . htmlspecialchars($row['name']) . "</p>";
        echo "</div>";
    }
    ?>
    </div>
</div>

<script>
const input   = document.getElementById("imageInput");
const message = document.getElementById("message");
const preview = document.getElementById("preview");

const MAX_WIDTH  = 1920;
const MAX_HEIGHT = 1080;
const MAX_SIZE   = 5 * 1024 * 1024;
const ALLOWED    = ["image/jpeg", "image/png", "image/gif", "image/webp"];

input.addEventListener("change", function () {
    const file = this.files[0];
    message.textContent = "";
    message.style.color = "";
    preview.innerHTML   = "";

    if (!file) return;

    // file type check
    if (!ALLOWED.includes(file.type)) {
        message.textContent = "❌ Invalid file type. Only JPEG, PNG, GIF, WEBP allowed.";
        message.style.color = "red";
        this.value = "";
        return;
    }

    // file size check
    if (file.size > MAX_SIZE) {
        message.textContent = "❌ File too large (max 5MB).";
        message.style.color = "red";
        this.value = "";
        return;
    }

    // dimension check via Image object
    const reader = new FileReader();
    reader.onload = e => {
        const img = new Image();
        img.onload = () => {
            if (img.width > MAX_WIDTH || img.height > MAX_HEIGHT) {
                message.textContent = `❌ Image too large (${img.width}×${img.height}px). Max allowed: ${MAX_WIDTH}×${MAX_HEIGHT}px.`;
                message.style.color = "red";
                input.value = "";
                preview.innerHTML = "";
                return;
            }
            // all good — show preview
            preview.innerHTML = `<img src="${e.target.result}">`;
            message.textContent = `✅ Image looks valid (${img.width}×${img.height}px).`;
            message.style.color = "green";
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
});
</script>

</body>
</html>