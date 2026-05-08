<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sugarlandia Barquillios System</title>
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
      max-width: 900px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    .badge {
      font-size: 12px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase;
      color: var(--primary); background: #e0e7ff;
      padding: 6px 16px; border-radius: 100px; margin-bottom: 24px;
    }
    h1 {
      font-size: clamp(2.4rem, 6vw, 3.5rem); font-weight: 800;
      line-height: 1.1; margin-bottom: 16px; color: var(--text-main);
    }
    h1 span { color: var(--primary); }
    .subtitle {
      font-size: 16px; font-weight: 400; color: var(--text-muted);
      margin-bottom: 48px; max-width: 600px; line-height: 1.6;
    }
    .cards {
      display: flex; gap: 24px; flex-wrap: wrap; justify-content: center; width: 100%;
    }
    .card {
      background: var(--card-bg); border: 1px solid var(--border-color);
      border-radius: 16px; padding: 40px 32px; width: 100%; max-width: 340px;
      text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
      transition: all 0.3s ease;
    }
    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
      border-color: var(--primary);
    }
    .icon-wrap {
      width: 64px; height: 64px; border-radius: 16px;
      background: #e0e7ff; color: var(--primary);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; margin-bottom: 24px;
    }
    .card-title { font-size: 20px; font-weight: 700; margin-bottom: 12px; color: var(--text-main); }
    .card-desc { font-size: 14px; color: var(--text-muted); text-align: center; line-height: 1.5; margin-bottom: 24px; }
    .card-btn {
      margin-top: auto; padding: 12px 24px; border-radius: 8px;
      font-size: 14px; font-weight: 600; background: var(--bg-color); color: var(--primary);
      border: 1px solid var(--border-color); width: 100%; transition: all 0.2s;
    }
    .card:hover .card-btn { background: var(--primary); color: white; border-color: var(--primary); }
    footer { margin-top: 60px; font-size: 13px; color: var(--text-muted); }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="badge">Sugarlandia Barquillios Bacolod</div>
  <h1>Welcome to the<br><span>Management Information System</span></h1>
  <p class="subtitle">Select your portal to continue and manage company operations securely.</p>

  <div class="cards">
    <!-- Superadmin Card -->
    <a href="superadmin_login.php" class="card">
      <div class="icon-wrap">🛡️</div>
      <div class="card-title">Superadmin</div>
      <p class="card-desc">System configuration, overarching company settings, and higher-level access.</p>
      <div class="card-btn">Enter as Superadmin &rarr;</div>
    </a>

    <!-- Admin Card -->
    <a href="login.php" class="card">
      <div class="icon-wrap">🏢</div>
      <div class="card-title">Admin</div>
      <p class="card-desc">Manage employees, payroll, attendance, and deductions for the company.</p>
      <div class="card-btn">Enter as Admin &rarr;</div>
    </a>
  </div>

  <footer>&copy; <?= date('Y') ?> Barquillio's Payroll System &nbsp;&middot;&nbsp; All rights reserved</footer>
</div>
</body>
</html>