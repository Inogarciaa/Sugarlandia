<?php
session_start();
require __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] === 'admin') {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = $user['user_id'];
                $_SESSION['admin_name']      = $user['name'];
                header("Location: index.php");
                exit;
            } else {
                $error = 'Access denied. Administrators only.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login – Barquillio's</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4f46e5;
      --primary-hover: #4338ca;
      --bg-color: #f8fafc;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
      --card-bg: #ffffff;
      --error-bg: #fef2f2;
      --error-text: #b91c1c;
      --error-border: #fca5a5;
    }
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-color);
      color: var(--text-main);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 40px 20px;
    }
    .wrapper {
      width: 100%;
      max-width: 440px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .badge {
      font-size: 12px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase;
      color: var(--primary); background: #e0e7ff;
      padding: 6px 16px; border-radius: 100px; margin-bottom: 24px;
    }
    h1 {
      font-size: 2.2rem; font-weight: 800;
      line-height: 1.2; margin-bottom: 12px; color: var(--text-main);
      text-align: center;
    }
    h1 span { color: var(--primary); }
    .subtitle {
      font-size: 15px; font-weight: 400; color: var(--text-muted);
      margin-bottom: 32px; text-align: center;
    }
    .card {
      background: var(--card-bg); border: 1px solid var(--border-color);
      border-radius: 16px; padding: 40px; width: 100%;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
    }
    .lock-icon {
      width: 64px; height: 64px; border-radius: 16px;
      background: #e0e7ff; color: var(--primary);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; margin: 0 auto 28px;
    }
    .field { margin-bottom: 20px; }
    .field label {
      display: block; font-size: 13px; font-weight: 600;
      color: var(--text-muted); margin-bottom: 8px;
    }
    .field input {
      width: 100%; padding: 12px 16px; border: 1px solid var(--border-color);
      border-radius: 8px; font-size: 15px; color: var(--text-main);
      font-family: inherit; outline: none; transition: all 0.2s;
    }
    .field input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    .field input::placeholder { color: #94a3b8; }
    .error-box {
      background: var(--error-bg); border: 1px solid var(--error-border);
      border-radius: 8px; padding: 12px 16px; font-size: 14px; color: var(--error-text);
      margin-bottom: 24px; display: flex; align-items: center; gap: 8px;
    }
    .btn-login {
      width: 100%; padding: 14px; margin-top: 12px;
      background: var(--primary); color: white; border: none; border-radius: 8px;
      font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
    }
    .btn-login:hover {
      background: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    .back-link {
      display: inline-flex; align-items: center; justify-content: center;
      margin-top: 32px; font-size: 14px; font-weight: 500;
      color: var(--text-muted); text-decoration: none; transition: color 0.2s;
    }
    .back-link:hover { color: var(--text-main); }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="badge">Barquillio's Company</div>
  <h1><span>Admin</span> Login</h1>
  <p class="subtitle">Secure access for administrators.</p>

  <div class="card">
    <div class="lock-icon">🔒</div>
    
    <?php if ($error): ?>
      <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="admin@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-login">Sign In &rarr;</button>
    </form>
  </div>

  <a href="homepage.php" class="back-link">&larr; Back to Home</a>
</div>
</body>
</html>