<?php 
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Portal – Barquillio's</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --dark:   #0d1117; --darker: #080c10;
      --gold:   #c9a84c; --gold2:  #e8c96d;
      --cream:  #f5efe0; --muted:  #8a8f9a;
      --card:   #13191f; --border: rgba(201,168,76,0.15);
    }
    body { font-family: 'DM Sans', sans-serif; background: var(--darker); color: var(--cream); min-height: 100vh; }
    body::before {
      content: ''; position: fixed; inset: 0;
      background-image: linear-gradient(rgba(201,168,76,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(201,168,76,0.03) 1px, transparent 1px);
      background-size: 48px 48px; pointer-events: none; z-index: 0;
    }
    nav {
      position: relative; z-index: 10;
      display: flex; align-items: center; justify-content: space-between;
      padding: 18px 40px; border-bottom: 1px solid var(--border);
      background: rgba(8,12,16,0.9); backdrop-filter: blur(12px);
    }
    .nav-logo { font-family: 'Playfair Display', serif; font-size: 1.15rem; color: var(--gold); }
    .nav-back {
      font-size: 13px; color: var(--muted); text-decoration: none;
      border: 1px solid var(--border); padding: 7px 16px; border-radius: 100px; transition: color 0.2s, border-color 0.2s;
    }
    .nav-back:hover { color: var(--gold); border-color: rgba(201,168,76,0.4); }

    main { position: relative; z-index: 1; max-width: 960px; margin: 0 auto; padding: 48px 24px; }

    .page-title { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 6px; }
    .page-title span { color: var(--gold); }
    .page-sub { font-size: 14px; color: var(--muted); margin-bottom: 36px; }

    .search-wrap { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 28px 32px; margin-bottom: 40px; }
    .search-wrap h3 { font-family: 'Playfair Display', serif; font-size: 1.05rem; margin-bottom: 14px; color: var(--gold2); }
    .search-row { display: flex; gap: 12px; flex-wrap: wrap; }
    .search-row input {
      flex: 1; min-width: 200px;
      background: rgba(255,255,255,0.04); border: 1px solid var(--border);
      border-radius: 10px; padding: 10px 16px; color: var(--cream);
      font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; transition: border-color 0.2s;
    }
    .search-row input:focus { border-color: rgba(201,168,76,0.5); }
    .search-row input::placeholder { color: var(--muted); }
    .search-row button {
      background: var(--gold); color: var(--darker); border: none; border-radius: 10px;
      padding: 10px 26px; font-family: 'DM Sans', sans-serif; font-weight: 500; font-size: 14px;
      cursor: pointer; transition: background 0.2s;
    }
    .search-row button:hover { background: var(--gold2); }

    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 40px; }
    .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 22px 20px; }
    .stat-label { font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
    .stat-val { font-family: 'Playfair Display', serif; font-size: 1.7rem; color: var(--gold2); }
    .stat-name { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--gold2); margin-top: 4px; }
    .stat-sub { font-size: 12px; color: var(--muted); margin-top: 4px; }

    .section { margin-bottom: 44px; }
    .section-header { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .section-header h2 { font-family: 'Playfair Display', serif; font-size: 1.2rem; }
    .pill { font-size: 11px; background: rgba(201,168,76,0.12); color: var(--gold); border: 1px solid rgba(201,168,76,0.2); padding: 3px 10px; border-radius: 100px; font-weight: 500; }

    .tbl-wrap { border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: rgba(201,168,76,0.07); }
    th { padding: 11px 16px; font-size: 11px; font-weight: 500; letter-spacing: 1.5px; text-transform: uppercase; color: var(--gold); text-align: left; border-bottom: 1px solid var(--border); }
    td { padding: 11px 16px; font-size: 13px; font-weight: 300; border-bottom: 1px solid rgba(201,168,76,0.06); color: #d0cfc8; }
    tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: rgba(201,168,76,0.03); }
    .empty-row td { text-align: center; color: var(--muted); padding: 28px; font-style: italic; }

    .s-present { color: #4ade80; } .s-absent { color: #f87171; } .s-late { color: #fbbf24; } .s-halfday { color: #60a5fa; }
    .t-ded { color: #f87171; } .t-all { color: #4ade80; }
    .err { color: #f87171; text-align: center; font-size: 15px; margin-top: 20px; }
  </style>
</head>
<body>
<nav>
  <div class="nav-logo">Barquillio's · User Portal</div>
  <a href="homepage.php" class="nav-back">← Back to Home</a>
</nav>

<main>
  <h1 class="page-title">Employee <span>Self-Service</span></h1>
  <p class="page-sub">Enter your Employee ID to view your personal records.</p>

  <div class="search-wrap">
    <h3>🔍 Look Up Your Records</h3>
    <form method="GET">
      <div class="search-row">
        <input type="number" name="emp_id" placeholder="Enter your Employee ID (e.g. 1)"
               value="<?= htmlspecialchars($_GET['emp_id'] ?? '') ?>" required>
        <button type="submit">Search</button>
      </div>
    </form>
  </div>

  <?php
  $emp_id = isset($_GET['emp_id']) ? (int)$_GET['emp_id'] : null;

  if ($emp_id):
    $stmt = $pdo->prepare("
      SELECT e.*, p.position_title, p.base_salary
      FROM employees e
      JOIN positions p ON e.position_id = p.position_id
      WHERE e.employee_id = ?
    ");
    $stmt->execute([$emp_id]);
    $emp = $stmt->fetch();

    if (!$emp): ?>
      <p class="err">❌ No employee found with ID <strong><?= $emp_id ?></strong>. Please check and try again.</p>
    <?php else:
      $attStmt = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM attendance WHERE employee_id = ? GROUP BY status");
      $attStmt->execute([$emp_id]);
      $attStats = [];
      foreach ($attStmt->fetchAll() as $row) $attStats[$row['status']] = $row['cnt'];

      $payStmt = $pdo->prepare("SELECT SUM(gross_pay) AS tg, SUM(net_pay) AS tn, COUNT(*) AS cnt FROM payroll WHERE employee_id = ?");
      $payStmt->execute([$emp_id]);
      $pay = $payStmt->fetch();
    ?>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-label">Employee</div>
        <div class="stat-name"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
        <div class="stat-sub"><?= htmlspecialchars($emp['position_title']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Days Present</div>
        <div class="stat-val"><?= $attStats['Present'] ?? 0 ?></div>
        <div class="stat-sub">Late: <?= $attStats['Late'] ?? 0 ?> · Absent: <?= $attStats['Absent'] ?? 0 ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Gross Pay</div>
        <div class="stat-val" style="font-size:1.3rem;">₱<?= number_format($pay['tg'] ?? 0, 2) ?></div>
        <div class="stat-sub"><?= $pay['cnt'] ?? 0 ?> payroll entries</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Net Pay</div>
        <div class="stat-val" style="font-size:1.3rem;">₱<?= number_format($pay['tn'] ?? 0, 2) ?></div>
        <div class="stat-sub">After all deductions</div>
      </div>
    </div>

    <div class="section">
      <div class="section-header"><h2>Attendance Records</h2><span class="pill">Last 20</span></div>
      <div class="tbl-wrap">
        <table>
          <thead><tr><th>#</th><th>Date</th><th>Status</th><th>Hours Worked</th></tr></thead>
          <tbody>
          <?php
            $r2 = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 20");
            $r2->execute([$emp_id]); $rows = $r2->fetchAll();
            if ($rows): foreach ($rows as $r):
              $cls = match($r['status']) { 'Present'=>'s-present','Absent'=>'s-absent','Late'=>'s-late','Half-Day'=>'s-halfday',default=>'' };
          ?>
            <tr><td><?= $r['attendance_id'] ?></td><td><?= $r['date'] ?></td>
                <td class="<?= $cls ?>"><?= $r['status'] ?></td><td><?= $r['hours_worked'] ?> hrs</td></tr>
          <?php endforeach; else: ?>
            <tr class="empty-row"><td colspan="4">No attendance records found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="section">
      <div class="section-header"><h2>Payslip History</h2><span class="pill">All</span></div>
      <div class="tbl-wrap">
        <table>
          <thead><tr><th>#</th><th>Pay Period</th><th>Gross Pay</th><th>Net Pay</th><th>Payment Date</th></tr></thead>
          <tbody>
          <?php
            $r2 = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? ORDER BY payment_date DESC");
            $r2->execute([$emp_id]); $rows = $r2->fetchAll();
            if ($rows): foreach ($rows as $r):
          ?>
            <tr><td><?= $r['payroll_id'] ?></td>
                <td><?= $r['pay_period_start'] ?> → <?= $r['pay_period_end'] ?></td>
                <td>₱<?= number_format($r['gross_pay'],2) ?></td>
                <td class="t-all">₱<?= number_format($r['net_pay'],2) ?></td>
                <td><?= $r['payment_date'] ?></td></tr>
          <?php endforeach; else: ?>
            <tr class="empty-row"><td colspan="5">No payroll records found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="section">
      <div class="section-header"><h2>Deductions &amp; Allowances</h2><span class="pill">Breakdown</span></div>
      <div class="tbl-wrap">
        <table>
          <thead><tr><th>Payroll #</th><th>Type</th><th>Item</th><th>Amount</th></tr></thead>
          <tbody>
          <?php
            $r2 = $pdo->prepare("SELECT da.* FROM deductions_allowances da JOIN payroll p ON da.payroll_id=p.payroll_id WHERE p.employee_id=? ORDER BY da.entry_id DESC");
            $r2->execute([$emp_id]); $rows = $r2->fetchAll();
            if ($rows): foreach ($rows as $r):
              $tc = $r['entry_type'] === 'Deduction' ? 't-ded' : 't-all';
          ?>
            <tr><td><?= $r['payroll_id'] ?></td>
                <td class="<?= $tc ?>"><?= $r['entry_type'] ?></td>
                <td><?= $r['item_type'] ?></td>
                <td class="<?= $tc ?>">₱<?= number_format($r['amount'],2) ?></td></tr>
          <?php endforeach; else: ?>
            <tr class="empty-row"><td colspan="4">No deduction/allowance records found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php endif; endif; ?>
</main>
</body>
</html>