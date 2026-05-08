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
  <title>Attendance Record</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f8; color: #2c3e50; padding: 30px; }
    form { max-width: 600px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    h2 { color: #2c3e50; margin-bottom: 20px; }
    label { display: block; margin-top: 14px; font-weight: bold; font-size: 14px; }
    input, select {
      width: 100%; padding: 9px 12px; margin-top: 5px;
      box-sizing: border-box; border: 1px solid #ccc;
      border-radius: 5px; font-size: 14px;
    }
    input:focus, select:focus { border-color: #2c3e50; outline: none; }
    input[type="submit"] {
      margin-top: 24px; background-color: #2c3e50; color: white;
      border: none; cursor: pointer; font-size: 15px; font-weight: bold;
      padding: 11px; border-radius: 5px; transition: background 0.2s;
    }
    input[type="submit"]:hover { background-color: #34495e; }
    .back { display: inline-block; margin-bottom: 20px; color: #2c3e50; text-decoration: none; font-size: 14px; }
    .back:hover { text-decoration: underline; }

    .lookup-wrap { position: relative; }
    .spinner {
      display: none; position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      width: 16px; height: 16px;
      border: 2px solid #ccc; border-top-color: #2c3e50;
      border-radius: 50%; animation: spin 0.6s linear infinite;
    }
    @keyframes spin { to { transform: translateY(-50%) rotate(360deg); } }

    #emp-info {
      display: none; margin-top: 10px;
      background: #eaf4fb; border: 1px solid #aed6f1;
      border-radius: 6px; padding: 10px 14px;
      font-size: 13px; color: #1a5276;
    }
    #emp-info strong { font-size: 13.5px; }
    #emp-info .emp-position { color: #2471a3; font-weight: 600; }
    #emp-error { display: none; margin-top: 8px; color: #c0392b; font-size: 12.5px; }

    #att-summary {
      display: none; margin-top: 16px;
      background: #fdfefe; border: 1px solid #dee2e6;
      border-radius: 8px; padding: 14px 18px;
    }
    #att-summary h3 {
      font-size: 13.5px; color: #2c3e50; margin-bottom: 10px;
      border-bottom: 1px solid #eee; padding-bottom: 7px;
    }
    .att-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; }
    .att-label { color: #555; }
    .att-val-absent { color: #e74c3c; font-weight: 700; }
    .att-val-late   { color: #f39c12; font-weight: 700; }
    .att-val-ok     { color: #27ae60; font-weight: 700; }
    .late-note {
      margin-top: 8px; font-size: 12px; color: #7f8c8d;
      background: #fef9e7; border: 1px solid #f9e79f;
      border-radius: 5px; padding: 6px 10px;
    }
    .warning-note {
      margin-top: 8px; font-size: 12.5px; color: #922b21;
      background: #fdedec; border: 1px solid #f5b7b1;
      border-radius: 5px; padding: 7px 10px; font-weight: 600;
    }
  </style>
</head>
<body>
  <a class="back" href="../index.php">← Back to Dashboard</a>

  <form action="attendance_data.php" method="POST">
    <h2>Attendance Record</h2>

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

    <div id="att-summary">
      <h3>📊 This Month's Attendance Summary</h3>
      <div class="att-row">
        <span class="att-label">Absences</span>
        <span id="sum-absent" class="att-val-absent">0</span>
      </div>
      <div class="att-row">
        <span class="att-label">Late instances</span>
        <span id="sum-late" class="att-val-late">0</span>
      </div>
      <div class="att-row">
        <span class="att-label">Late → Absent conversions</span>
        <span id="sum-late-abs" class="att-val-absent">0</span>
      </div>
      <div class="att-row" style="border-top:1px solid #eee; margin-top:6px; padding-top:6px;">
        <span class="att-label" style="font-weight:bold;">Total deductible absences</span>
        <span id="sum-total-abs" class="att-val-absent" style="font-size:14px;">0</span>
      </div>
      <div class="late-note">⏰ Every <strong>3 late</strong> instances = <strong>1 absent</strong> deduction</div>
      <div id="late-warning" class="warning-note" style="display:none;"></div>
    </div>

    <label>Date:</label>
    <input type="date" name="date" id="att_date" readonly required>
    

    <label>Status:</label>
    <select name="status" id="att_status" required>
      <option value="">-- Select Status --</option>
      <option value="Present">Present</option>
      <option value="Absent">Absent</option>
      <option value="Late">Late</option>
      <option value="Half-Day">Half-Day</option>
    </select>

    <label>Hours Worked:</label>
    <input type="number" name="hours_worked" id="hours_worked" min="0" max="24" step="1" required>

    <input type="submit" value="Submit Attendance">
  </form>

  <script>
    let lookupTimer = null;

    document.getElementById('employee_id').addEventListener('change', function () {
      const empId   = this.value;
      const infoBox = document.getElementById('emp-info');
      const errBox  = document.getElementById('emp-error');
      const spinner = document.getElementById('spinner');
      const sumBox  = document.getElementById('att-summary');
      const warnBox = document.getElementById('late-warning');

      infoBox.style.display = 'none';
      errBox.style.display  = 'none';
      sumBox.style.display  = 'none';
      warnBox.style.display = 'none';
      infoBox.innerHTML     = '';

      if (!empId) {
        return;
      }

      spinner.style.display = 'block';

      clearTimeout(lookupTimer);
      lookupTimer = setTimeout(() => {
        fetch('get_attendance_summary.php?employee_id=' + encodeURIComponent(empId))
          .then(r => r.json())
          .then(data => {
            spinner.style.display = 'none';

            if (data.error) {
              errBox.style.display = 'block';
              return;
            }

            infoBox.innerHTML =
              `👤 <strong>${data.first_name} ${data.last_name}</strong> &nbsp;|&nbsp;
               <span class="emp-position">${data.position_title}</span> &nbsp;|&nbsp;
               Base Salary: <strong>₱${parseFloat(data.base_salary).toLocaleString('en-PH', {
                 minimumFractionDigits: 2,
                 maximumFractionDigits: 2
               })}</strong>`;
            infoBox.style.display = 'block';

            const absentCount = parseInt(data.absent_count || 0);
            const lateCount   = parseInt(data.late_count || 0);
            const lateAbs     = Math.floor(lateCount / 3);
            const totalAbs    = absentCount + lateAbs;

            document.getElementById('sum-absent').textContent    = absentCount;
            document.getElementById('sum-late').textContent      = lateCount;
            document.getElementById('sum-late-abs').textContent  = lateAbs;
            document.getElementById('sum-total-abs').textContent = totalAbs;

            if (lateCount > 0 && lateCount % 3 === 0) {
              warnBox.textContent = `⚠️ This employee already has ${lateCount} late record(s) this month, equivalent to ${lateAbs} absent deduction(s).`;
              warnBox.style.display = 'block';
            } else if (lateCount > 0) {
              const remaining = 3 - (lateCount % 3);
              warnBox.textContent = `⚠️ This employee needs ${remaining} more late record(s) to equal another absent deduction.`;
              warnBox.style.display = 'block';
            }

            sumBox.style.display = 'block';
          })
          .catch(() => {
            spinner.style.display = 'none';
            errBox.textContent = '⚠️ Could not reach server. Please try again.';
            errBox.style.display = 'block';
          });
      }, 300);
    });

    document.getElementById('att_status').addEventListener('change', function () {
  const hoursInput = document.getElementById('hours_worked');
  const status = this.value;

  switch (status) {
    case 'Present':
      hoursInput.value = 8;
      hoursInput.readOnly = true;
      break;

    case 'Half-Day':
      hoursInput.value = 4;
      hoursInput.readOnly = true;
      break;

    case 'Absent':
      hoursInput.value = 0;
      hoursInput.readOnly = true;
      break;

    case 'Late':
      hoursInput.value = 7; // optional
      hoursInput.readOnly = false;
      break;

    default:
      hoursInput.value = '';
      hoursInput.readOnly = false;
  }
});
  

    window.addEventListener('DOMContentLoaded', () => {
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('att_date').value = today;
    });
  </script>
</body>
</html>