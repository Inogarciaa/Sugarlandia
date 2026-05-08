<?php
require '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Entry</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    :root {
      --primary: #4f46e5; --primary-hover: #4338ca;
      --bg-color: #f8fafc; --text-main: #0f172a;
      --text-muted: #64748b; --border-color: #e2e8f0; --card-bg: #ffffff;
    }
    body {
      font-family: 'Inter', sans-serif; background: var(--bg-color); color: var(--text-main);
      padding: 40px 20px; display: flex; flex-direction: column; align-items: center;
    }
    form {
      width: 100%; max-width: 600px; background: var(--card-bg); padding: 40px;
      border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
      border: 1px solid var(--border-color); box-sizing: border-box; margin-bottom: 40px;
    }
    h2 { font-size: 24px; font-weight: 700; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); margin-top: 0; width: 100%; max-width: 600px; }
    label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-top: 20px; margin-bottom: 6px; }
    input:not([type="submit"]):not([type="file"]), select {
      width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px;
      font-size: 14px; box-sizing: border-box; outline: none; transition: all 0.2s; font-family: inherit; color: var(--text-main);
    }
    input:focus:not([type="submit"]):not([type="file"]), select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
    input[type="submit"] {
      margin-top: 32px; width: 100%; background: var(--primary); color: white; border: none;
      border-radius: 8px; font-size: 15px; font-weight: 600; padding: 14px; cursor: pointer; transition: all 0.2s; font-family: inherit;
    }
    input[type="submit"]:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .back {
      display: inline-flex; margin-bottom: 24px; color: var(--text-muted); text-decoration: none;
      font-size: 14px; font-weight: 600; transition: color 0.2s;
      width: 100%; max-width: 600px; /* Align with form */
    }
    .back:hover { color: var(--text-main); }

    /* FIXED: preview image capped at 200px */
    #preview img {
      margin-top: 10px;
      width: 200px;
      height: 200px;
      object-fit: cover;
      border-radius: 8px;
      display: block;
    }

    .dim-note { font-size: 12px; color: #888; margin-top: 6px; }

    /* gallery */
    .gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 15px;
      margin-top: 15px;
      max-width: 700px;
    }
    .card {
      background: #fafafa;
      border-radius: 10px;
      padding: 10px;
      text-align: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    /* FIXED: gallery images capped at fixed height */
    .card img {
      width: 100%;
      height: 120px;
      object-fit: cover;
      border-radius: 8px;
    }
    .card p { font-size: 12px; margin-top: 4px; word-break: break-word; color: #333; text-align: left; }
    .card p span.label { font-weight: bold; color: #2c3e50; }

    /* clickable image */
    .card img { cursor: zoom-in; transition: transform 0.2s; }
    .card img:hover { transform: scale(1.04); }

    .alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; max-width: 600px; }
    .alert-error   { background: #ffe5e5; color: #c0392b; border: 1px solid #f5c6cb; }
    .alert-success { background: #e5ffed; color: #1e8449; border: 1px solid #b7dfb8; }

    /* lightbox */
    .lightbox {
      display: none; position: fixed; inset: 0; z-index: 9999;
      background: rgba(0,0,0,0.85);
      align-items: center; justify-content: center;
      flex-direction: column; gap: 12px;
    }
    .lightbox.active { display: flex; }
    .lightbox img {
      max-width: 90vw; max-height: 78vh;
      border-radius: 10px; box-shadow: 0 8px 40px rgba(0,0,0,0.6);
      animation: zoomIn 0.2s ease;
    }
    .lightbox-info { color: #fff; font-size: 14px; text-align: center; line-height: 1.8; }
    .lightbox-info .lb-name { font-size: 18px; font-weight: bold; }
    .lightbox-close {
      position: fixed; top: 16px; right: 22px;
      font-size: 34px; color: white; cursor: pointer;
      background: none; border: none; line-height: 1;
    }
    .lightbox-close:hover { color: #f87171; }
    @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
  </style>
</head>

<body>
  <a class="back" href="../index.php">← Back to Dashboard</a>

  <?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>
  <?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>

  <form action="products_data.php" method="POST" enctype="multipart/form-data">
    <h2>Product Entry</h2>

    <label>Product Name:</label>
    <input type="text" name="product_name" required>

    <label>Variant:</label>
    <input type="text" name="product_variant" placeholder="Example: Original, Ube, Small Pack, Large Pack" required>

    <label>Price:</label>
    <input type="number" name="price" min="0" step="1" required>

    <label>Stock Quantity:</label>
    <input type="number" name="stock_quantity" min="0" step="1" value="0" required>

    <label>Image:</label>
    <input id="imageInput" type="file" name="image" accept="image/*" required>
    <p class="dim-note">Max size: 5MB &nbsp;|&nbsp; Max dimensions: 1920×1080px &nbsp;|&nbsp; Formats: JPEG, PNG, GIF, WEBP</p>
    <p id="message" style="margin-top:8px; font-size:13px;"></p>
    <div id="preview"></div>

    <input type="submit" value="Save Product">
  </form>

  <br>
  <h2>Uploaded Products</h2>
  <div class="gallery">
    <?php
    $stmt   = $pdo->query("SELECT * FROM products");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($images as $row) {
        $imgSrc = htmlspecialchars("../uploads/" . $row['image']);
        $name    = htmlspecialchars($row['product_name']);
        $variant = htmlspecialchars($row['product_variant']);
        echo "<div class='card'>";
        $file = "../uploads/" . $row['image'];
        if (file_exists($file)) {
            echo "<img src='$imgSrc' onclick=\"openLightbox('$imgSrc','$name','$variant')\" alt='$name'>";
        }
        echo "<p><span class='label'>Product:</span> $name</p>";
        echo "<p><span class='label'>Variant:</span> $variant</p>";
        echo "</div>";
    }
    ?>
  </div>

  <!-- Lightbox -->
  <div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">✕</button>
    <img id="lightbox-img" src="" alt="">
    <div class="lightbox-info">
      <div class="lb-name" id="lightbox-name"></div>
      <div id="lightbox-variant"></div>
    </div>
  </div>

</body>

<script>
function openLightbox(src, name, variant) {
    document.getElementById('lightbox-img').src     = src;
    document.getElementById('lightbox-name').textContent    = 'Product: ' + name;
    document.getElementById('lightbox-variant').textContent = 'Variant: ' + variant;
    document.getElementById('lightbox').classList.add('active');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

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

    if (!ALLOWED.includes(file.type)) {
        message.textContent = "❌ Invalid file type. Only JPEG, PNG, GIF, WEBP allowed.";
        message.style.color = "red";
        this.value = "";
        return;
    }

    if (file.size > MAX_SIZE) {
        message.textContent = "❌ File too large (max 5MB).";
        message.style.color = "red";
        this.value = "";
        return;
    }

    // ADDED: dimension check
    const reader = new FileReader();
    reader.onload = e => {
        const img = new Image();
        img.onload = () => {
            if (img.width > MAX_WIDTH || img.height > MAX_HEIGHT) {
                message.textContent = `❌ Image too large (${img.width}×${img.height}px). Max: ${MAX_WIDTH}×${MAX_HEIGHT}px.`;
                message.style.color = "red";
                input.value = "";
                preview.innerHTML = "";
                return;
            }
            preview.innerHTML   = `<img src="${e.target.result}">`;
            message.textContent = `✅ Image valid (${img.width}×${img.height}px).`;
            message.style.color = "green";
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
});
</script>
</html>