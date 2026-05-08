<?php
require '../config.php';

$employees = $pdo->query("
    SELECT employee_id, first_name, last_name
    FROM employees
    ORDER BY first_name ASC, last_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payroll Entry</title>
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
      width: 100%; max-width: 640px; background: var(--card-bg); padding: 40px;
      border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
      border: 1px solid var(--border-color); box-sizing: border-box;
    }
    h2 { font-size: 24px; font-weight: 700; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); margin-top: 0; }
    label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-top: 20px; margin-bottom: 6px; }
    input:not([type="submit"]):not([type="file"]), select {
      width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px;
      font-size: 14px; box-sizing: border-box; outline: none; transition: all 0.2s; font-family: inherit; color: var(--text-main);
    }
    input:focus:not([type="submit"]):not([type="file"]), select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
    input[readonly] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }
    input[type="submit"] {
      margin-top: 32px; width: 100%; background: var(--primary); color: white; border: none;
      border-radius: 8px; font-size: 15px; font-weight: 600; padding: 14px; cursor: pointer; transition: all 0.2s; font-family: inherit;
    }
    input[type="submit"]:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .back {
      display: inline-flex; margin-bottom: 24px; color: var(--text-muted); text-decoration: none;
      font-size: 14px; font-weight: 600; transition: color 0.2s;
      width: 100%; max-width: 640px; /* Align with form */
    }
    .back:hover { color: var(--text-main); }

    #deduction-box {
      display: none;
      margin-top: 22px;
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 18px 20px;
    }
    #deduction-box h3 {
      font-size: 14px;
      color: #2c3e50;
      margin-bottom: 12px;
      border-bottom: 1px solid #dee2e6;
      padding-bottom: 8px;
    }
    .ded-row {
      display: flex;
      justify-content: space-between;
      font-size: 13.5px;
      padding: 5px 0;
      border-bottom: 1px dashed #e0e0e0;
    }
    .ded-row:last-child { border-bottom: none; }
    .ded-label { color: #555; }
    .ded-amount { color: #e74c3c; font-weight: 600; }
    .ded-gross  { color: #2c3e50; font-weight: 600; }
    .ded-net    { color: #27ae60; font-weight: 700; font-size: 14.5px; }
    .ded-total-row {
      display: flex;
      justify-content: space-between;
      font-size: 13.5px;
      padding: 8px 0 5px;
      border-top: 2px solid #2c3e50;
      margin-top: 4px;
    }
    .badge {
      display: inline-block; font-size: 11px; padding: 2px 7px;
      border-radius: 10px; margin-left: 6px; vertical-align: middle;
    }
    .badge-gov { background: #d6eaf8; color: #1a5276; }

    #emp-info {
      display: none;
      margin-top: 10px;
      background: #eaf4fb;
      border: 1px solid #aed6f1;
      border-radius: 6px;
      padding: 10px 14px;
      font-size: 13px;
      color: #1a5276;
    }
    #emp-info strong { font-size: 13.5px; }
    #emp-info .emp-position { color: #2471a3; font-weight: 600; }
    #emp-error {
      display: none;
      margin-top: 8px;
      color: #c0392b;
      font-size: 12.5px;
    }
    .lookup-wrap { position: relative; }
    .spinner {
      display: none;
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      width: 16px; height: 16px;
      border: 2px solid #ccc;
      border-top-color: #2c3e50;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
    @keyframes spin { to { transform: translateY(-50%) rotate(360deg); } }
  </style>
</head>
<body>
  <a class="back" href="../index.php">← Back to Dashboard</a>
  <form action="payroll_data.php" method="POST" id="payrollForm">
    <h2>Payroll Entry</h2>

    <label>Employee Name:</label>
    <div class="lookup-wrap">
      <select name="employee_id" id="employee_id" required>
        <option value="">-- Select Employee --</option>
        <?php foreach ($employees as $emp): ?>
          <option value="<?= htmlspecialchars($emp['employee_id']) ?>">
            <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="spinner" id="spinner"></div>
    </div>
    <div id="emp-info"></div>
    <div id="emp-error">⚠️ Employee not found. Please check the selection.</div>

    <label>Gross Pay (₱) <small style="font-weight:normal;color:#888">— auto-computed from attendance × daily rate</small></label>
    <input type="number" name="gross_pay" id="gross_pay" step="0.01" min="0" readonly required
           placeholder="Will auto-fill once Employee is selected">

    <input type="hidden" name="sss_contribution"        id="h_sss">
    <input type="hidden" name="philhealth_contribution" id="h_philhealth">
    <input type="hidden" name="pagibig_contribution"    id="h_pagibig">
    <input type="hidden" name="attendance_deduction"    id="h_att_ded" value="0">

    <div id="deduction-box">
      <h3>📊 Pay Computation</h3>

      <div id="earnings-section">
        <div class="ded-row" style="background:#f0fdf4; border-radius:4px; padding:8px; margin-bottom:6px;">
          <span class="ded-label">
            📅 Days Worked
            <span id="days-detail" style="font-size:11px; color:#888; display:block; margin-top:2px;"></span>
          </span>
          <span style="color:#16a34a; font-weight:700;" id="show_days">0 days</span>
        </div>
        <div class="ded-row">
          <span class="ded-label">Daily Rate × Days Worked</span>
          <span class="ded-amount ded-gross" id="show_gross">₱0.00</span>
        </div>
      </div>

      <div style="margin-top:8px; padding-top:8px; border-top:1px dashed #ddd;">
        <div class="ded-row">
          <span class="ded-label">SSS Contribution <span class="badge badge-gov">Gov't Mandated</span></span>
          <span class="ded-amount" id="show_sss">- ₱0.00</span>
        </div>
        <div class="ded-row">
          <span class="ded-label">PhilHealth Contribution <span class="badge badge-gov">Gov't Mandated</span></span>
          <span class="ded-amount" id="show_philhealth">- ₱0.00</span>
        </div>
        <div class="ded-row">
          <span class="ded-label">Pag-IBIG Contribution <span class="badge badge-gov">Gov't Mandated</span></span>
          <span class="ded-amount" id="show_pagibig">- ₱0.00</span>
        </div>
      </div>

      <div class="ded-total-row">
        <span style="font-weight:bold; color:#e74c3c;">Total Deductions</span>
        <span class="ded-amount" id="show_total_ded">- ₱0.00</span>
      </div>
      <div class="ded-total-row">
        <span style="font-weight:bold; color:#27ae60;">Net Pay</span>
        <span class="ded-net" id="show_net">₱0.00</span>
      </div>
    </div>

    <label>Net Pay (₱) <small style="font-weight:normal;color:#888">— auto-computed after deductions</small></label>
    <input type="number" name="net_pay" id="net_pay" step="0.01" min="0" readonly required>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:4px;">
      <div>
        <label>Pay Period Start:</label>
        <input type="date" name="pay_period_start" id="pay_period_start" required>
      </div>
      <div>
        <label>Pay Period End:</label>
        <input type="date" name="pay_period_end" id="pay_period_end" required>
      </div>
    </div>

    <label>Payment Date:</label>
    <input type="date" name="payment_date" id="payment_date" readonly required>
    <input type="submit" value="Submit Payroll">
  </form>

  <script>
    window.addEventListener('DOMContentLoaded', () => {
      const now = new Date();
      const today = now.toISOString().split('T')[0];
      document.getElementById('payment_date').value = today;

      // Auto-fill pay period: 1st to last day of current month
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const lastDay = new Date(year, now.getMonth() + 1, 0).getDate();
      document.getElementById('pay_period_start').value = `${year}-${month}-01`;
      document.getElementById('pay_period_end').value = `${year}-${month}-${String(lastDay).padStart(2, '0')}`;
    });

    const SSS_TABLE = [
      [0,       3249.99,  135.00],[3250,    3749.99,  157.50],[3750,    4249.99,  180.00],
      [4250,    4749.99,  202.50],[4750,    5249.99,  225.00],[5250,    5749.99,  247.50],
      [5750,    6249.99,  270.00],[6250,    6749.99,  292.50],[6750,    7249.99,  315.00],
      [7250,    7749.99,  337.50],[7750,    8249.99,  360.00],[8250,    8749.99,  382.50],
      [8750,    9249.99,  405.00],[9250,    9749.99,  427.50],[9750,   10249.99,  450.00],
      [10250,  10749.99,  472.50],[10750,  11249.99,  495.00],[11250,  11749.99,  517.50],
      [11750,  12249.99,  540.00],[12250,  12749.99,  562.50],[12750,  13249.99,  585.00],
      [13250,  13749.99,  607.50],[13750,  14249.99,  630.00],[14250,  14749.99,  652.50],
      [14750,  15249.99,  675.00],[15250,  15749.99,  697.50],[15750,  16249.99,  720.00],
      [16250,  16749.99,  742.50],[16750,  17249.99,  765.00],[17250,  17749.99,  787.50],
      [17750,  18249.99,  810.00],[18250,  18749.99,  832.50],[18750,  19249.99,  855.00],
      [19250,  19749.99,  877.50],[19750,  20249.99,  900.00],[20250,  20749.99,  922.50],
      [20750,  21249.99,  945.00],[21250,  21749.99,  967.50],[21750,  22249.99,  990.00],
      [22250,  22749.99, 1012.50],[22750,  23249.99, 1035.00],[23250,  23749.99, 1057.50],
      [23750,  24249.99, 1080.00],[24250,  24749.99, 1102.50],[24750,  25249.99, 1125.00],
      [25250,  25749.99, 1147.50],[25750,  26249.99, 1170.00],[26250,  26749.99, 1192.50],
      [26750,  27249.99, 1215.00],[27250,  27749.99, 1237.50],[27750,  28249.99, 1260.00],
      [28250,  28749.99, 1282.50],[28750,  29249.99, 1305.00],[29250,  29749.99, 1327.50],
      [29750,  Infinity,  1350.00],
    ];

    function computeSSS(g) {
      for (let r of SSS_TABLE) {
        if (g >= r[0] && g <= r[1]) return r[2];
      }
      return 1350;
    }

    function computePhilHealth(g) {
      let c = g * 0.025;
      if (c < 500) c = 500;
      if (c > 2500) c = 2500;
      return +c.toFixed(2);
    }

    function computePagIbig(g) {
      let c = g * (g <= 1500 ? 0.01 : 0.02);
      if (c > 100) c = 100;
      return +c.toFixed(2);
    }

    function fmt(n) {
      return '₱' + parseFloat(n).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }

    let _attDed = 0, _attDetail = '';

    function updateDeductions(gross) {
      const box = document.getElementById('deduction-box');

      if (!gross || gross <= 0) {
        box.style.display = 'none';
        document.getElementById('net_pay').value = '';
        return;
      }

      const sss        = computeSSS(gross);
      const philhealth = computePhilHealth(gross);
      const pagibig    = computePagIbig(gross);
      const totalDed   = +(sss + philhealth + pagibig).toFixed(2);
      const net        = +(gross - totalDed).toFixed(2);

      document.getElementById('show_gross').textContent      = fmt(gross);
      document.getElementById('show_sss').textContent        = '- ' + fmt(sss);
      document.getElementById('show_philhealth').textContent = '- ' + fmt(philhealth);
      document.getElementById('show_pagibig').textContent    = '- ' + fmt(pagibig);
      document.getElementById('show_total_ded').textContent  = '- ' + fmt(totalDed);
      document.getElementById('show_net').textContent        = fmt(net);

      document.getElementById('h_sss').value        = sss.toFixed(2);
      document.getElementById('h_philhealth').value = philhealth.toFixed(2);
      document.getElementById('h_pagibig').value    = pagibig.toFixed(2);
      document.getElementById('h_att_ded').value    = '0.00';
      document.getElementById('net_pay').value      = net.toFixed(2);

      box.style.display = 'block';
    }

    document.getElementById('gross_pay').addEventListener('input', function () {
      updateDeductions(parseFloat(this.value) || 0);
    });

    document.getElementById('employee_id').addEventListener('change', function () {
      const empId   = this.value;
      const infoBox = document.getElementById('emp-info');
      const errBox  = document.getElementById('emp-error');
      const spinner = document.getElementById('spinner');
      const grossEl = document.getElementById('gross_pay');

      infoBox.style.display = 'none';
      errBox.style.display  = 'none';
      infoBox.innerHTML     = '';

      if (!empId) {
        grossEl.value = '';
        updateDeductions(0);
        document.getElementById('show_days').textContent = '0 days';
        document.getElementById('days-detail').textContent = '';
        return;
      }

      spinner.style.display = 'block';

      fetch('get_employee_salary.php?employee_id=' + encodeURIComponent(empId))
        .then(r => r.json())
        .then(data => {
          spinner.style.display = 'none';

          if (data.error) {
            errBox.style.display = 'block';
            grossEl.value = '';
            updateDeductions(0);
          } else {
            // Show employee info
            infoBox.innerHTML =
              `👤 <strong>${data.first_name} ${data.last_name}</strong> &nbsp;|&nbsp;
               <span class="emp-position">${data.position_title}</span>
               <br><span style="color:#2471a3; font-size:12px;">💰 Daily Rate: <strong>${fmt(data.daily_rate)}</strong>/day (8 hrs) · ${fmt(data.hourly_rate)}/hr</span>
               <br><span style="font-size:12px; color:#555;">📅 This month: <strong style="color:#16a34a">${data.present_count} Present</strong>, <strong style="color:#e74c3c">${data.absent_count} Absent</strong>, <strong style="color:#f39c12">${data.late_count} Late</strong>, <strong style="color:#3498db">${data.half_day_count} Half-Day</strong></span>`;

            infoBox.style.display = 'block';

            // Show days worked info
            document.getElementById('show_days').textContent = data.days_worked + ' days';
            document.getElementById('days-detail').textContent =
              `${data.present_count} present + ${data.late_count} late + ${data.half_day_count} half-day (×0.5) = ${data.days_worked} days × ${fmt(data.daily_rate)}/day`;

            // Set gross pay from attendance
            grossEl.value = parseFloat(data.gross_pay).toFixed(2);
            updateDeductions(parseFloat(data.gross_pay));
          }
        })
        .catch(() => {
          spinner.style.display = 'none';
          errBox.textContent    = '⚠️ Could not reach server. Please try again.';
          errBox.style.display  = 'block';
        });
    });
  </script>
</body>
</html>