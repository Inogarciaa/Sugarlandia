<?php
session_start();
require 'config.php';
require 'auth.php';  

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['_action'])) {
    $action = $_POST['_action'];

    /* ---------- EMPLOYEES ---------- */
    if ($action === 'update_employee') {
        $profile_picture = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['profile_picture']['tmp_name'];
            $name = basename($_FILES['profile_picture']['name']);
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $new_name = uniqid('emp_') . '.' . $ext;
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                $profile_picture = $new_name;
            }
        }

        if ($profile_picture) {
            $stmt = $pdo->prepare("UPDATE employees SET first_name=:fn, last_name=:ln, gender=:g,
                email=:em, phone_number=:ph, hire_date=:hd, position_id=:pid, profile_picture=:pp
                WHERE employee_id=:id");
            $stmt->execute([
                ':fn'  => trim($_POST['first_name']),
                ':ln'  => trim($_POST['last_name']),
                ':g'   => trim($_POST['gender']),
                ':em'  => trim($_POST['email']),
                ':ph'  => trim($_POST['phone_number']),
                ':hd'  => trim($_POST['hire_date']),
                ':pid' => (int)$_POST['position_id'],
                ':pp'  => $profile_picture,
                ':id'  => (int)$_POST['employee_id']
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE employees SET first_name=:fn, last_name=:ln, gender=:g,
                email=:em, phone_number=:ph, hire_date=:hd, position_id=:pid
                WHERE employee_id=:id");
            $stmt->execute([
                ':fn'  => trim($_POST['first_name']),
                ':ln'  => trim($_POST['last_name']),
                ':g'   => trim($_POST['gender']),
                ':em'  => trim($_POST['email']),
                ':ph'  => trim($_POST['phone_number']),
                ':hd'  => trim($_POST['hire_date']),
                ':pid' => (int)$_POST['position_id'],
                ':id'  => (int)$_POST['employee_id']
            ]);
        }
        header("Location: index.php?success=" . urlencode("Employee updated successfully."));
        exit;
    }
    if ($action === 'delete_employee') {
        $emp_id = (int)$_POST['employee_id'];

        // SOFT DELETE: just mark as archived, keep all payroll/attendance data intact
        $pdo->prepare("UPDATE employees SET is_archived = 1 WHERE employee_id = ?")->execute([$emp_id]);

        header("Location: index.php?success=" . urlencode("Employee archived successfully."));
        exit;
    }

    if ($action === 'restore_employee') {
        $emp_id = (int)$_POST['employee_id'];
        $pdo->prepare("UPDATE employees SET is_archived = 0 WHERE employee_id = ?")->execute([$emp_id]);
        header("Location: index.php?success=" . urlencode("Employee restored successfully."));
        exit;
    }

    /* ---------- PRODUCTS ---------- */
if ($action === 'update_product') {
    $stmt = $pdo->prepare("UPDATE products SET product_name=:pn, product_variant=:pv, price=:pr, stock_quantity=:sq WHERE product_id=:id");
    $stmt->execute([
        ':pn' => trim($_POST['product_name']),
        ':pv' => trim($_POST['product_variant']),
        ':pr' => (int)$_POST['price'],
        ':sq' => (int)$_POST['stock_quantity'],
        ':id' => (int)$_POST['product_id'],
    ]);
    header("Location: index.php?success=" . urlencode("Product updated successfully."));
    exit;
}

if ($action === 'delete_product') {
    $pdo->prepare("DELETE FROM products WHERE product_id=?")->execute([(int)$_POST['product_id']]);
    header("Location: index.php?success=" . urlencode("Product deleted."));
    exit;
}

    /* ---------- ATTENDANCE ---------- */
    if ($action === 'insert_attendance') {
        $emp_id = (int)$_POST['employee_id'];
        $status = trim($_POST['status']);
        $date = date('Y-m-d');
        
        $hours = 0;
        if ($status === 'Present' || $status === 'Late') {
            $hours = 8;
        } elseif ($status === 'Half-Day') {
            $hours = 4;
        }

        $check = $pdo->prepare("SELECT attendance_id FROM attendance WHERE employee_id = ? AND date = ?");
        $check->execute([$emp_id, $date]);
        if ($check->rowCount() > 0) {
            header("Location: index.php?error=" . urlencode("Attendance for today already recorded."));
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status, hours_worked) VALUES (?, ?, ?, ?)");
        $stmt->execute([$emp_id, $date, $status, $hours]);
        
        header("Location: index.php?success=" . urlencode("Attendance recorded successfully."));
        exit;
    }

    if ($action === 'update_attendance') {
        $stmt = $pdo->prepare("UPDATE attendance SET date=:d, status=:s, hours_worked=:h WHERE attendance_id=:id");
        $stmt->execute([
            ':d'  => trim($_POST['date']),
            ':s'  => trim($_POST['status']),
            ':h'  => (float)$_POST['hours_worked'],
            ':id' => (int)$_POST['attendance_id'],
        ]);
        header("Location: index.php?success=" . urlencode("Attendance updated successfully."));
        exit;
    }
    if ($action === 'delete_attendance') {
        $pdo->prepare("DELETE FROM attendance WHERE attendance_id=?")->execute([(int)$_POST['attendance_id']]);
        header("Location: index.php?success=" . urlencode("Attendance record deleted."));
        exit;
    }

    /* ---------- PAYROLL ---------- */
    if ($action === 'update_payroll') {
        $stmt = $pdo->prepare("UPDATE payroll SET pay_period_start=:ps, pay_period_end=:pe,
            gross_pay=:gp, net_pay=:np, payment_date=:pd WHERE payroll_id=:id");
        $stmt->execute([
            ':ps' => trim($_POST['pay_period_start']),
            ':pe' => trim($_POST['pay_period_end']),
            ':gp' => (float)$_POST['gross_pay'],
            ':np' => (float)$_POST['net_pay'],
            ':pd' => trim($_POST['payment_date']),
            ':id' => (int)$_POST['payroll_id'],
        ]);
        header("Location: index.php?success=" . urlencode("Payroll updated successfully."));
        exit;
    }
    if ($action === 'delete_payroll') {
        $pay_id = (int)$_POST['payroll_id'];
        $pdo->prepare("DELETE FROM deductions_allowances WHERE payroll_id=?")->execute([$pay_id]);
        $pdo->prepare("DELETE FROM payroll WHERE payroll_id=?")->execute([$pay_id]);
        header("Location: index.php?success=" . urlencode("Payroll record deleted."));
        exit;
    }

    /* ---------- DEDUCTIONS / ALLOWANCES ---------- */
    if ($action === 'update_deduction') {
        $stmt = $pdo->prepare("UPDATE deductions_allowances SET entry_type=:et, item_type=:it, amount=:am WHERE entry_id=:id");
        $stmt->execute([
            ':et' => trim($_POST['entry_type']),
            ':it' => trim($_POST['item_type']),
            ':am' => (float)$_POST['amount'],
            ':id' => (int)$_POST['entry_id'],
        ]);
        header("Location: index.php?success=" . urlencode("Entry updated successfully."));
        exit;
    }
    if ($action === 'delete_deduction') {
        $pdo->prepare("DELETE FROM deductions_allowances WHERE entry_id=?")->execute([(int)$_POST['entry_id']]);
        header("Location: index.php?success=" . urlencode("Entry deleted."));
        exit;
    }

    /* ---------- RESERVATIONS ---------- */
    if ($action === 'update_reservation') {
        $stmt = $pdo->prepare("UPDATE reservations SET status=? WHERE reservation_id=?");
        $stmt->execute([trim($_POST['status']), (int)$_POST['reservation_id']]);
        header("Location: index.php#section-reservations");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard – Barquillio's</title>
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
  <link rel="stylesheet" href="index.css"> <!-- FIXED: was missing https: -->
</head>
<body>

<header>
  <div class="logo">Sugarlandia Barquillios</div>
  <nav>
    <span style="font-size:13px;color:rgba(255,255,255,0.55);padding:0 6px;">
       <?= isset($_SESSION['superadmin_logged_in']) ? 'Super Admin' : htmlspecialchars($_SESSION['Admin_name'] ?? $_SESSION['admin_name'] ?? 'Admin') ?>
    </span>
    <a href="homepage.php" class="home-link">⬅ Home</a>
    <a href="logout.php" style="border-color:rgba(231,76,60,0.5);color:#e74c3c;">🚪 Logout</a>
  </nav>
</header>

<div class="dashboard-layout">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-title">Menu</div>
  <nav class="sidebar-nav">
    <a href="#" class="sidebar-link active" data-target="section-employees">Employee</a>
    <a href="#" class="sidebar-link" data-target="section-attendance">Attendance</a>
    <a href="#" class="sidebar-link" data-target="section-payroll">Payroll</a>
    <a href="#" class="sidebar-link" data-target="section-products">Product</a>
    <a href="#" class="sidebar-link" data-target="section-reservations">Reservations</a>
    <a href="#" class="sidebar-link" data-target="section-archived">Archived</a>
  </nav>
</aside>

<main class="main-content">
  <div id="alert-container">
    <?php if (!empty($_GET['success'])): ?>
      <div class="success">✅ <?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="error" style="background: #fee; color: #c0392b; padding: 12px 16px; border-left: 4px solid #c0392b; margin-bottom: 20px; border-radius: 4px;">❌ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
  </div>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const alertContainer = document.getElementById('alert-container');
      if (alertContainer && alertContainer.innerHTML.trim() !== '') {
        const url = new URL(window.location);
        url.searchParams.delete('success');
        url.searchParams.delete('error');
        window.history.replaceState({}, document.title, url);
        
        setTimeout(() => {
          alertContainer.style.transition = "opacity 0.5s ease";
          alertContainer.style.opacity = "0";
          setTimeout(() => alertContainer.remove(), 500);
        }, 5000);
      }
    });
  </script>

  <h1>Admin Dashboard</h1>
  <p class="subtitle"></p>

  <!-- ============================================================
       EMPLOYEES TABLE
  ============================================================ -->
  <div class="section" id="section-employees">
    <div class="section-head">
      <h2> Employees</h2>
      <a href="employee/employee.php">+ Add Employee</a>
    </div>
    <div class="search-wrap">
      <input type="text" id="search-employees" placeholder="Search by name, position, email…" oninput="filterTable('search-employees','tbl-employees','emp-count')">
      <span class="search-count" id="emp-count"></span>
    </div>
    <div class="tbl-wrap">
      <table id="tbl-employees">
        <thead>
          <tr>
            <th>Name</th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $rows = $pdo->query("
            SELECT e.employee_id, e.first_name, e.last_name, e.gender, e.profile_picture,
                   e.email, e.phone_number, e.hire_date, e.position_id, p.position_title
            FROM employees e
            JOIN positions p ON e.position_id = p.position_id
            WHERE e.is_archived = 0
            ORDER BY e.employee_id DESC
          ")->fetchAll();
          if ($rows):
            foreach ($rows as $r):
              $eid = $r['employee_id'];
        ?>
          <tr class="clickable-row" data-name="<?= htmlspecialchars(strtolower($r['first_name'].' '.$r['last_name'].' '.$r['position_title'].' '.$r['email'])) ?>" onclick="openEmpProfile(<?= $eid ?>)">
            <td>
              <div style="display: flex; align-items: center; gap: 12px;">
                <?php if (!empty($r['profile_picture'])): ?>
                  <img src="uploads/<?= htmlspecialchars($r['profile_picture']) ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
                <?php else: ?>
                  <div style="width:32px; height:32px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:14px;"></div>
                <?php endif; ?>
                <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>
              </div>
            </td>
            <td>
              <div class="actions-cell" onclick="event.stopPropagation()">
                <button class="btn btn-edit" onclick='openEditEmployee(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)'>Edit</button>
                <form method="POST" onsubmit="return confirm('Archive this employee? Their payroll and attendance records will be kept.')">
                  <input type="hidden" name="_action" value="delete_employee">
                  <input type="hidden" name="employee_id" value="<?= $eid ?>">
                  <button type="submit" class="btn btn-delete">Archive</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr class="empty"><td colspan="2">No employees found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================================
       ARCHIVED EMPLOYEES TABLE
  ============================================================ -->
  <div class="section" id="section-archived" style="display:none;">
    <div class="section-head">
      <h2>🗄️ Archived Employees</h2>
    </div>
    <div class="tbl-wrap">
      <table id="tbl-archived">
        <thead>
          <tr>
            <th>Name</th>
            <th>Position</th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $archived = $pdo->query("
            SELECT e.employee_id, e.first_name, e.last_name, e.profile_picture, p.position_title
            FROM employees e
            JOIN positions p ON e.position_id = p.position_id
            WHERE e.is_archived = 1
            ORDER BY e.last_name ASC
          ")->fetchAll();
          if ($archived):
            foreach ($archived as $r):
              $eid = $r['employee_id'];
        ?>
          <tr>
            <td>
              <div style="display: flex; align-items: center; gap: 12px;">
                <?php if (!empty($r['profile_picture'])): ?>
                  <img src="uploads/<?= htmlspecialchars($r['profile_picture']) ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover; opacity:0.6;">
                <?php else: ?>
                  <div style="width:32px; height:32px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center;"></div>
                <?php endif; ?>
                <span style="color:#888;"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></span>
              </div>
            </td>
            <td style="color:#aaa;"><?= htmlspecialchars($r['position_title']) ?></td>
            <td>
              <div class="actions-cell">
                <form method="POST" onsubmit="return confirm('Restore this employee to active?')">
                  <input type="hidden" name="_action" value="restore_employee">
                  <input type="hidden" name="employee_id" value="<?= $eid ?>">
                  <button type="submit" class="btn btn-edit">Restore</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr class="empty"><td colspan="3">No archived employees.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- PRODUCT EDIT MODAL -->
<div class="modal-overlay" id="editProductModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('editProductModal')">&times;</button>
    <h3>Edit Product</h3>

    <form method="POST">
      <input type="hidden" name="_action" value="update_product">
      <input type="hidden" name="product_id" id="ep_product_id">

      <label>Product Name</label>
      <input type="text" name="product_name" id="ep_product_name" required>

      <label>Variant</label> 
      <input type="text" name="product_variant" id="ep_product_variant" required>

      <label>Price</label>
      <input type="number" name="price" id="ep_price" min="0" step="1" readonly required>

      <label>Stock Quantity</label>
      <input type="number" name="stock_quantity" id="ep_stock_quantity" min="0" step="1" required>
      <div class="modal-actions">
        <button type="submit" class="btn-save">Save Changes</button>
        <button type="button" class="btn-cancel" onclick="closeModal('editProductModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

  <!-- ============================================================
     PRODUCTS TABLE
============================================================ -->
<div class="section" id="section-products" style="display:none;">
  <div class="section-head">
    <h2>Products</h2>
    <a href="products/products.php">+ Add Product</a>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Product Name</th>
          <th>Variant</th>
          <th>Price</th>
          <th>Stock Quantity</th>
          <th class="actions-col">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php
        $rows = $pdo->query("
          SELECT product_id, product_name, product_variant, price, stock_quantity
          FROM products
          ORDER BY product_id DESC
        ")->fetchAll();

        if ($rows):
          foreach ($rows as $r):
      ?>
        <tr>
          <td><?= $r['product_id'] ?></td>
          <td><?= htmlspecialchars($r['product_name']) ?></td>
          <td><?= htmlspecialchars($r['product_variant']) ?></td>
          <td>₱<?= number_format($r['price'], 2) ?></td>
          <td><?= htmlspecialchars($r['stock_quantity']) ?></td>
          <td>
            <div class="actions-cell">
              <button class="btn btn-edit" onclick='openEditProduct(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)'>Edit</button>
              <form method="POST" onsubmit="return confirm('Delete this product?')">
                <input type="hidden" name="_action" value="delete_product">
                <input type="hidden" name="product_id" value="<?= $r['product_id'] ?>">
                <button type="submit" class="btn btn-delete">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr class="empty"><td colspan="6">No products found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

  <!-- ============================================================
       ATTENDANCE TABLE
  ============================================================ -->
  <div class="section" id="section-attendance" style="display:none;">
    <div class="section-head">
      <h2>Attendance Overview</h2>
      <a href="#" onclick="openCheckAttendance(); return false;">Check Attendance</a>
    </div>
    <div class="search-wrap" style="display:flex; justify-content:flex-end;">
      <select id="attendance-month-filter" onchange="renderAttendanceTable()" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; cursor: pointer; outline: none; background: white; min-width: 200px;">
         <!-- Populated by JS -->
      </select>
    </div>
    <div class="tbl-wrap">
      <table id="tbl-attendance-overview">
        <thead>
          <tr>
            <th>Employee Name</th>
            <th>Present</th>
            <th>Absent</th>
            <th>Late</th>
            <th>Half-Day</th>
            <th>Total Deductible Absences</th>
          </tr>
        </thead>
        <tbody id="attendance-overview-body">
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================================
       PAYROLL TABLE
  ============================================================ -->
  <div class="section" id="section-payroll" style="display:none;">
    <div class="section-head">
      <h2>Payroll</h2>
    </div>
    <div class="search-wrap" style="display:flex; justify-content:flex-end; margin-bottom:15px;">
      <select id="payroll-month-filter" onchange="filterPayrollTable()" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; cursor: pointer; outline: none; background: white; min-width: 200px;">
         <option value="all">All Months</option>
         <?php
           $payMonths = $pdo->query("SELECT DISTINCT SUBSTRING(payment_date, 1, 7) as m FROM payroll WHERE payment_date IS NOT NULL ORDER BY m DESC")->fetchAll();
           foreach($payMonths as $pm):
             $mVal = $pm['m'];
             if(!$mVal) continue;
             $mName = date('F Y', strtotime($mVal . '-01'));
         ?>
             <option value="<?= $mVal ?>"><?= $mName ?></option>
         <?php endforeach; ?>
      </select>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Employee</th><th>Period Start</th><th>Period End</th>
            <th>Gross Pay</th><th>Net Pay</th><th>Status</th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $rows = $pdo->query("
            SELECT p.payroll_id, p.pay_period_start, p.pay_period_end, p.gross_pay, p.net_pay, p.payment_date,
                   p.is_paid, p.paid_date,
                   CONCAT(e.first_name,' ',e.last_name) AS name, e.profile_picture
            FROM payroll p
            JOIN employees e ON p.employee_id = e.employee_id
            ORDER BY p.payment_date DESC
          ")->fetchAll();
          if ($rows):
            foreach ($rows as $r):
        ?>
          <tr class="payroll-row clickable-row" data-date="<?= htmlspecialchars($r['payment_date']) ?>" onclick="openPayslip(<?= $r['payroll_id'] ?>)">
            <td>
              <div style="display: flex; align-items: center; gap: 12px;">
                <?php if (!empty($r['profile_picture'])): ?>
                  <img src="uploads/<?= htmlspecialchars($r['profile_picture']) ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
                <?php else: ?>
                  <div style="width:32px; height:32px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center;"></div>
                <?php endif; ?>
                <?= htmlspecialchars($r['name']) ?>
              </div>
            </td>
            <td><?= $r['pay_period_start'] ?></td>
            <td><?= $r['pay_period_end'] ?></td>
            <td>₱<?= number_format($r['gross_pay'], 2) ?></td>
            <td style="color:#27ae60;font-weight:600;">₱<?= number_format($r['net_pay'], 2) ?></td>
            <td>
              <?php if ($r['is_paid']): ?>
                <span style="background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:600;">✅ Paid<?= $r['paid_date'] ? ' · '.$r['paid_date'] : '' ?></span>
              <?php else: ?>
                <span style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:600;">⏳ Unpaid</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="actions-cell" onclick="event.stopPropagation()">
                <button class="btn btn-edit" onclick='openEditPayroll(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)'>Edit</button>
                <button class="btn btn-edit" style="background:#f0fdf4;color:#16a34a;" onclick='openPayslip(<?= $r["payroll_id"] ?>)'>Payslip</button>
                <form method="POST" onsubmit="return confirm('Delete this payroll record?')">
                  <input type="hidden" name="_action" value="delete_payroll">
                  <input type="hidden" name="payroll_id" value="<?= $r['payroll_id'] ?>">
                  <button type="submit" class="btn btn-delete">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr class="empty"><td colspan="8">No payroll records found.</td></tr>
        <?php endif; ?>
          <tr class="empty" id="payroll-empty-row" style="display:none;"><td colspan="8">No payroll records found for the selected month.</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================================
       DEDUCTIONS & ALLOWANCES TABLE
  ============================================================ -->
  <div class="section" id="section-deductions" style="display:none;">
    <div class="section-head">
      <h2>Deductions &amp; Allowances</h2>
      <a href="deduction/deduction.php">+ Add Entry</a>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Payroll #</th><th>Employee</th>
            <th>Type</th><th>Item</th><th>Amount</th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $rows = $pdo->query("
            SELECT da.entry_id, da.payroll_id, da.entry_type, da.item_type, da.amount,
                   CONCAT(e.first_name,' ',e.last_name) AS name
            FROM deductions_allowances da
            JOIN payroll   p ON da.payroll_id = p.payroll_id
            JOIN employees e ON p.employee_id = e.employee_id
            ORDER BY da.entry_id DESC
          ")->fetchAll();
          if ($rows):
            foreach ($rows as $r):
              $tc = $r['entry_type'] === 'Deduction' ? 't-ded' : 't-all';
        ?>
          <tr>
            <td><?= $r['entry_id'] ?></td>
            <td><?= $r['payroll_id'] ?></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td class="<?= $tc ?>"><?= htmlspecialchars($r['entry_type']) ?></td>
            <td><?= htmlspecialchars($r['item_type']) ?></td>
            <td class="<?= $tc ?>">₱<?= number_format($r['amount'], 2) ?></td>
            <td>
              <div class="actions-cell">
                <button class="btn btn-edit" onclick='openEditDeduction(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)'>Edit</button>
                <form method="POST" onsubmit="return confirm('Delete this entry?')">
                  <input type="hidden" name="_action" value="delete_deduction">
                  <input type="hidden" name="entry_id" value="<?= $r['entry_id'] ?>">
                  <button type="submit" class="btn btn-delete">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr class="empty"><td colspan="7">No deduction/allowance records found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================================
       RESERVATIONS TABLE
  ============================================================ -->
  <div class="section" id="section-reservations" style="display:none;">
    <div class="section-head">
      <h2>Reservations</h2>
      <a href="store/" target="_blank">View Store</a>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Pickup Date</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $rows = $pdo->query("SELECT * FROM reservations ORDER BY created_at DESC")->fetchAll();
          if ($rows):
            foreach ($rows as $r):
              $badgeClass = '';
              if ($r['status'] == 'Pending') $badgeClass = 'background:#fef3c7;color:#92400e;';
              if ($r['status'] == 'Confirmed') $badgeClass = 'background:#dbeafe;color:#1e40af;';
              if ($r['status'] == 'Completed') $badgeClass = 'background:#d1fae5;color:#065f46;';
              if ($r['status'] == 'Cancelled') $badgeClass = 'background:#fee2e2;color:#b91c1c;';
        ?>
          <tr>
            <td>#<?= str_pad($r['reservation_id'], 5, '0', STR_PAD_LEFT) ?></td>
            <td><?= htmlspecialchars($r['customer_name']) ?></td>
            <td><?= htmlspecialchars($r['customer_phone']) ?></td>
            <td><?= htmlspecialchars($r['pickup_date']) ?></td>
            <td style="font-weight:600;">₱<?= number_format($r['total_amount'], 2) ?></td>
            <td><span style="<?= $badgeClass ?>padding:4px 8px;border-radius:12px;font-size:12px;font-weight:600;"><?= $r['status'] ?></span></td>
            <td>
              <div class="actions-cell">
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="_action" value="update_reservation">
                  <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                  <select name="status" onchange="this.form.submit()" style="padding:4px;border-radius:4px;border:1px solid #ccc;font-size:12px;cursor:pointer;">
                    <option value="Pending" <?= $r['status']=='Pending'?'selected':'' ?>>Pending</option>
                    <option value="Confirmed" <?= $r['status']=='Confirmed'?'selected':'' ?>>Confirmed</option>
                    <option value="Completed" <?= $r['status']=='Completed'?'selected':'' ?>>Completed</option>
                    <option value="Cancelled" <?= $r['status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
                  </select>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr class="empty"><td colspan="7">No reservations found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main> <!-- /.main-content -->
</div> <!-- /.dashboard-layout -->

<!-- ============================================================
     PAYSLIP MODAL
============================================================ -->
<style>
  .payslip-modal { max-width: 720px; padding: 0; overflow: hidden; }
  .payslip-header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff; padding: 28px 32px 20px; }
  .payslip-header h3 { color: #fff; border: none; margin: 0; font-size: 22px; }
  .payslip-header .payslip-subtitle { font-size: 13px; color: rgba(255,255,255,0.75); margin-top: 4px; }
  .payslip-body { padding: 24px 32px 32px; max-height: 65vh; overflow-y: auto; }
  .payslip-emp-row { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
  .payslip-emp-pic { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }
  .payslip-emp-name { font-weight: 700; font-size: 16px; color: #0f172a; }
  .payslip-emp-pos { font-size: 13px; color: #64748b; }
  .payslip-info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px; }
  .payslip-info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; }
  .payslip-info-card .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; }
  .payslip-info-card .value { font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 2px; }
  .payslip-info-card .value.green { color: #16a34a; }
  .payslip-info-card .value.blue { color: #2563eb; }
  .payslip-section-title { font-size: 14px; font-weight: 700; color: #334155; margin: 20px 0 10px; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
  .payslip-daily-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .payslip-daily-table th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
  .payslip-daily-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
  .payslip-daily-table tr:last-child td { border-bottom: none; }
  .payslip-daily-table .day-present { color: #16a34a; font-weight: 600; }
  .payslip-daily-table .day-absent { color: #dc2626; font-weight: 600; }
  .payslip-daily-table .day-late { color: #d97706; font-weight: 600; }
  .payslip-daily-table .day-halfday { color: #2563eb; font-weight: 600; }
  .payslip-ded-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; border-bottom: 1px dashed #e2e8f0; }
  .payslip-ded-row:last-child { border-bottom: none; }
  .payslip-ded-label { color: #475569; }
  .payslip-ded-amount { color: #dc2626; font-weight: 600; }
  .payslip-total-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 15px; font-weight: 700; border-top: 2px solid #334155; margin-top: 8px; }
  .payslip-total-row .net { color: #16a34a; }
  .payslip-badge { display: inline-block; font-size: 10px; padding: 2px 6px; border-radius: 10px; background: #dbeafe; color: #1e40af; margin-left: 4px; font-weight: 600; }
  .payslip-print-btn { background: #4f46e5; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit; }
  .payslip-print-btn:hover { background: #4338ca; transform: translateY(-1px); }
  @media print {
    /* Hide everything on the page */
    body > *:not(#modalPayslip) { display: none !important; }
    header, .dashboard-layout, .sidebar, .main-content { display: none !important; }

    /* Reset modal overlay to be a normal block, not a fixed overlay */
    #modalPayslip {
      display: block !important;
      position: static !important;
      width: 100% !important;
      height: auto !important;
      background: #fff !important;
      overflow: visible !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    /* Reset the inner modal box */
    #modalPayslip .modal {
      position: static !important;
      width: 100% !important;
      max-width: 100% !important;
      height: auto !important;
      box-shadow: none !important;
      border: none !important;
      border-radius: 0 !important;
      margin: 0 !important;
      overflow: visible !important;
    }

    /* Hide close button and print button */
    #modalPayslip .modal-close,
    #modalPayslip .payslip-print-btn,
    #modalPayslip select { display: none !important; }

    /* Let content flow naturally */
    .payslip-body { max-height: none !important; overflow: visible !important; }
    .payslip-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
<div class="modal-overlay" id="modalPayslip">
  <div class="modal payslip-modal">
    <button class="modal-close" onclick="closeModal('modalPayslip')">✕</button>
    <div class="payslip-header">
      <h3>📄 Payslip</h3>
      <div class="payslip-subtitle">Sugarlandia Barquillios — Employee Pay Statement</div>
    </div>
    <div class="payslip-body" id="payslip-content">
      <div class="profile-loading">Loading payslip…</div>
    </div>
  </div>
</div>

<!-- ============================================================
     EMPLOYEE PROFILE MODAL (Attendance + Payroll tabs)
============================================================ -->
<div class="modal-overlay" id="modalEmpProfile">
  <div class="modal" style="max-width:560px;">
    <button class="modal-close" onclick="closeModal('modalEmpProfile')">✕</button>

    <div class="profile-header">
      <div class="profile-avatar" id="prof-avatar" style="overflow:hidden; display:flex; align-items:center; justify-content:center;"></div>
      <div>
        <div class="profile-name" id="prof-name">—</div>
        <div class="profile-pos"  id="prof-pos">—</div>
      </div>
    </div>

    <div class="profile-tabs">
      <div class="profile-tab active" onclick="switchProfileTab('tab-info')">Info</div>
      <div class="profile-tab"       onclick="switchProfileTab('tab-attendance')">Attendance</div>
      <div class="profile-tab"       onclick="switchProfileTab('tab-payroll')">Payslip</div>
    </div>

    <!-- Info Pane -->
    <div class="profile-pane active" id="tab-info">
      <div class="profile-info-grid">
        <div class="info-item"><label>Email</label><span id="prof-email">—</span></div>
        <div class="info-item"><label>Phone</label><span id="prof-phone">—</span></div>
        <div class="info-item"><label>Gender</label><span id="prof-gender">—</span></div>
        <div class="info-item"><label>Da of Birth</label><span id="prof-dob">—</span></div>
        <div class="info-item"><label> </label><span id="prof-hire">—</span></div>
        <div class="info-item"><label></label><span id="prof-address">—</span></div>
      </div>
    </div>

    <!-- Attendance Pane -->
    <div class="profile-pane" id="tab-attendance">
      <div style="display:flex; justify-content:flex-end; gap: 8px; margin-bottom:10px;">
        <form method="POST" style="margin: 0;">
          <input type="hidden" name="_action" value="insert_attendance">
          <input type="hidden" name="employee_id" class="att-quick-emp-id" value="">
          <input type="hidden" name="status" value="Present">
          <button type="submit" class="btn-profile-action" style="background: #27ae60; color: white; border: none; cursor: pointer;">Present</button>
        </form>
        <form method="POST" style="margin: 0;">
          <input type="hidden" name="_action" value="insert_attendance">
          <input type="hidden" name="employee_id" class="att-quick-emp-id" value="">
          <input type="hidden" name="status" value="Absent">
          <button type="submit" class="btn-profile-action" style="background: #e74c3c; color: white; border: none; cursor: pointer;">Absent</button>
        </form>
        <form method="POST" style="margin: 0;">
          <input type="hidden" name="_action" value="insert_attendance">
          <input type="hidden" name="employee_id" class="att-quick-emp-id" value="">
          <input type="hidden" name="status" value="Late">
          <button type="submit" class="btn-profile-action" style="background: #f39c12; color: white; border: none; cursor: pointer;">Late</button>
        </form>
      </div>
      <div id="prof-att-body"><div class="profile-loading">Loading…</div></div>
    </div>

    <!-- Payslip Pane -->
    <div class="profile-pane" id="tab-payroll">
      <div id="prof-pay-body" style="max-height:55vh;overflow-y:auto;"><div class="profile-loading">Loading…</div></div>
    </div>
  </div>
</div>

<!-- ============================================================
     EDIT MODALS
============================================================ -->

<!-- EMPLOYEE MODAL -->
<div class="modal-overlay" id="modalEmployee">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modalEmployee')">✕</button>
    <h3>Edit Employee</h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="_action" value="update_employee">
      <input type="hidden" name="employee_id" id="emp_id">

      <label>First Name</label>
      <input type="text" name="first_name" id="emp_first_name" required>

      <label>Last Name</label>
      <input type="text" name="last_name" id="emp_last_name" required>

      <label>Gender</label>
      <select name="gender" id="emp_gender" required>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>

      <label>Email</label>
      <input type="email" name="email" id="emp_email" required>

      <label>Phone Number</label>
      <input type="text" name="phone_number" id="emp_phone" required>

      <label>Hire Date</label>
      <input type="date" name="hire_date" id="emp_hire_date" required>

      <label>Position ID </label>
      <select name="position_id" id="emp_position_id" required>
        <option value="">-- Select Position --</option>
        <option value="1">Manager</option>
        <option value="2">Pastry Chef</option>
        <option value="3">Cashier</option>
        <option value="4">Staff</option>
      </select>

      <label>Update Profile Picture (Optional)</label>
      <input type="file" name="profile_picture" accept="image/*">

      <div class="modal-actions">
        <button type="submit" class="btn-save">Save Changes</button>
        <button type="button" class="btn-cancel" onclick="closeModal('modalEmployee')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- CHECK ATTENDANCE MODAL -->
<style>
.ca-status-btn { padding: 10px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 6px; cursor: pointer; font-weight: bold; color: #555; transition: all 0.2s; text-align: center; }
.ca-status-btn:hover { background: #e9ecef; }
.ca-status-btn[data-val="Present"].selected { background: #27ae60; color: white; border-color: #27ae60; }
.ca-status-btn[data-val="Absent"].selected { background: #e74c3c; color: white; border-color: #e74c3c; }
.ca-status-btn[data-val="Late"].selected { background: #f39c12; color: white; border-color: #f39c12; }
.ca-status-btn[data-val="Half-Day"].selected { background: #3498db; color: white; border-color: #3498db; }
.ca-emp-item { display: flex; align-items: center; gap: 12px; padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; transition: background 0.2s; }
.ca-emp-item:hover { background: #f1f5f9; }
.ca-emp-item:last-child { border-bottom: none; }
</style>
<div class="modal-overlay" id="modalCheckAttendance">
  <div class="modal" style="overflow:visible;">
    <button class="modal-close" onclick="closeModal('modalCheckAttendance')">✕</button>
    <h3>Check Attendance</h3>
    <form method="POST" id="formCheckAttendance" onsubmit="return ca_validateForm()">
      <input type="hidden" name="_action" value="insert_attendance">
      
      <label>Employee</label>
      <div style="position:relative;">
        <input type="text" id="ca_search_emp" placeholder="Search by name..." autocomplete="off" oninput="ca_filterEmployees()" onfocus="ca_filterEmployees()" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px; box-sizing:border-box;">
        <div id="ca_emp_list" style="position:absolute; top:100%; left:0; right:0; max-height:200px; overflow-y:auto; background:white; border:1px solid #ccc; border-top:none; border-radius:0 0 5px 5px; z-index:10; display:none; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        </div>
      </div>
      
      <div id="ca_selected_emp_display" style="display:none; align-items:center; gap:12px; padding:10px; border:1px solid #27ae60; border-radius:5px; margin-top:10px; background:#eafaf1;">
         <div id="ca_sel_pic" style="width:32px; height:32px; border-radius:50%; overflow:hidden;"></div>
         <span id="ca_sel_name" style="flex-grow:1; font-weight:bold; color:#2c3e50;"></span>
         <button type="button" onclick="ca_clearSelection()" style="background:none; border:none; color:#c0392b; cursor:pointer; font-size:16px;">✕</button>
      </div>
      <input type="hidden" name="employee_id" id="ca_selected_emp_id">

      <label style="margin-top:20px;">Status</label>
      <input type="hidden" name="status" id="ca_selected_status">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:8px;" id="ca_status_btns">
        <div class="ca-status-btn" data-val="Present" onclick="ca_selectStatus(this, 'Present')">Present</div>
        <div class="ca-status-btn" data-val="Absent" onclick="ca_selectStatus(this, 'Absent')">Absent</div>
        <div class="ca-status-btn" data-val="Late" onclick="ca_selectStatus(this, 'Late')">Late</div>
        <div class="ca-status-btn" data-val="Half-Day" onclick="ca_selectStatus(this, 'Half-Day')">Half-Day</div>
      </div>

      <div class="modal-actions" style="margin-top:24px;">
        <button type="submit" class="btn-save">Save</button>
        <button type="button" class="btn-cancel" onclick="closeModal('modalCheckAttendance')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ATTENDANCE MODAL -->
<div class="modal-overlay" id="modalAttendance">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modalAttendance')">✕</button>
    <h3>Edit Attendance</h3>
    <form method="POST">
      <input type="hidden" name="_action" value="update_attendance">
      <input type="hidden" name="attendance_id" id="att_id">

      <label>Date</label>
      <input type="date" name="date" id="att_date" required>

      <label>Status</label>
      <select name="status" id="att_status" required>
        <option value="Present">Present</option>
        <option value="Absent">Absent</option>
        <option value="Late">Late</option>
        <option value="Half-Day">Half-Day</option>
      </select>

      <label>Hours Worked</label>
      <input type="number" name="hours_worked" id="att_hours" step="0.01" min="0" max="24" required>

      <div class="modal-actions">
        <button type="submit" class="btn-save">Save Changes</button>
        <button type="button" class="btn-cancel" onclick="closeModal('modalAttendance')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- PAYROLL MODAL -->
<div class="modal-overlay" id="modalPayroll">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modalPayroll')">✕</button>
    <h3>Edit Payroll</h3>
    <form method="POST">
      <input type="hidden" name="_action" value="update_payroll">
      <input type="hidden" name="payroll_id" id="pay_id">

      <label>Pay Period Start</label>
      <input type="date" name="pay_period_start" id="pay_start" required>

      <label>Pay Period End</label>
      <input type="date" name="pay_period_end" id="pay_end" required>

      <label>Gross Pay (₱)</label>
      <input type="number" name="gross_pay" id="pay_gross" step="0.01" min="0" required>

      <label>Net Pay (₱)</label>
      <input type="number" name="net_pay" id="pay_net" step="0.01" min="0" required>

      <label>Payment Date</label>
      <input type="date" name="payment_date" id="pay_date" required>

      <div class="modal-actions">
        <button type="submit" class="btn-save">Save Changes</button>
        <button type="button" class="btn-cancel" onclick="closeModal('modalPayroll')">Cancel</button>
      </div>
    </form>
  </div>
</div>


<script>
  /* ---- helpers ---- */
  function openModal(id)  { document.getElementById(id).classList.add('open'); }
  function closeModal(id) { document.getElementById(id).classList.remove('open'); }

  // Close on backdrop click
  document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
  });

  // Close on Escape key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(el => el.classList.remove('open'));
    }
  });

  function setVal(id, val) { document.getElementById(id).value = val; }
  function setSelect(id, val) {
    const el = document.getElementById(id);
    for (let i = 0; i < el.options.length; i++) {
      if (el.options[i].value === val) { el.selectedIndex = i; break; }
    }
  }

  /* ---- Employee ---- */
  function openEditEmployee(r) {
    setVal('emp_id',          r.employee_id);
    setVal('emp_first_name',  r.first_name);
    setVal('emp_last_name',   r.last_name);
    setSelect('emp_gender',   r.gender);
    setVal('emp_email',       r.email);
    setVal('emp_phone',       r.phone_number);
    setVal('emp_hire_date',   r.hire_date);
    setSelect('emp_position_id', r.position_id);
    openModal('modalEmployee');
  }

  /* ---- Attendance ---- */
  function openCheckAttendance() {
    ca_clearSelection();
    document.getElementById('ca_search_emp').value = '';
    
    // clear status
    document.querySelectorAll('.ca-status-btn').forEach(btn => btn.classList.remove('selected'));
    document.getElementById('ca_selected_status').value = '';
    
    ca_filterEmployees();
    openModal('modalCheckAttendance');
  }

  function ca_filterEmployees() {
    const q = document.getElementById('ca_search_emp').value.toLowerCase();
    const list = document.getElementById('ca_emp_list');
    let html = '';
    
    Object.values(empData).filter(emp => {
      const name = (emp.first_name + ' ' + emp.last_name).toLowerCase();
      return name.includes(q);
    }).sort((a,b) => a.first_name.localeCompare(b.first_name)).forEach(emp => {
      const pic = emp.profile_picture ? `uploads/${emp.profile_picture}` : '';
      const picHtml = pic ? `<img src="${pic}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">` 
                          : `<div style="width:32px;height:32px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;"></div>`;
                          
      html += `<div class="ca-emp-item" onclick="ca_selectEmployee(${emp.employee_id})">
                 ${picHtml}
                 <span style="font-weight:bold;">${emp.first_name} ${emp.last_name}</span>
               </div>`;
    });
    
    if (html === '') {
      html = '<div style="padding:10px; color:#888;">No employees found</div>';
    }
    
    list.innerHTML = html;
    list.style.display = 'block';
  }
  
  function ca_selectEmployee(id) {
    const emp = empData[id];
    if (!emp) return;
    
    document.getElementById('ca_selected_emp_id').value = id;
    document.getElementById('ca_search_emp').style.display = 'none';
    document.getElementById('ca_emp_list').style.display = 'none';
    
    const picDiv = document.getElementById('ca_sel_pic');
    if (emp.profile_picture) {
      picDiv.innerHTML = `<img src="uploads/${emp.profile_picture}" style="width:100%;height:100%;object-fit:cover;">`;
    } else {
      picDiv.innerHTML = `<div style="width:100%;height:100%;background:#e2e8f0;"></div>`;
    }
    
    document.getElementById('ca_sel_name').textContent = emp.first_name + ' ' + emp.last_name;
    document.getElementById('ca_selected_emp_display').style.display = 'flex';
  }
  
  function ca_clearSelection() {
    document.getElementById('ca_selected_emp_id').value = '';
    document.getElementById('ca_selected_emp_display').style.display = 'none';
    document.getElementById('ca_search_emp').style.display = 'block';
    document.getElementById('ca_search_emp').value = '';
    document.getElementById('ca_search_emp').focus();
  }
  
  function ca_selectStatus(btn, val) {
    document.querySelectorAll('.ca-status-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('ca_selected_status').value = val;
  }
  
  function ca_validateForm() {
    if (!document.getElementById('ca_selected_emp_id').value) {
      alert('Please select an employee.');
      return false;
    }
    if (!document.getElementById('ca_selected_status').value) {
      alert('Please select a status.');
      return false;
    }
    return true;
  }
  
  // Close the employee list when clicking outside
  document.addEventListener('click', function(e) {
    if (e.target.closest('#ca_emp_list') || e.target.id === 'ca_search_emp') return;
    const list = document.getElementById('ca_emp_list');
    if (list) list.style.display = 'none';
  });

  function openEditAttendance(r) {
    setVal('att_id',    r.attendance_id);
    setVal('att_date',  r.date);
    setSelect('att_status', r.status);
    setVal('att_hours', r.hours_worked);
    openModal('modalAttendance');
  }

  /* ---- Payroll ---- */
  function openEditPayroll(r) {
    setVal('pay_id',    r.payroll_id);
    setVal('pay_start', r.pay_period_start);
    setVal('pay_end',   r.pay_period_end);
    setVal('pay_gross', r.gross_pay);
    setVal('pay_net',   r.net_pay);
    setVal('pay_date',  r.payment_date);
    openModal('modalPayroll');
  }

  /* ---- Payslip ---- */
  function fmt$(n) {
    return '₱' + parseFloat(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  let _payslipEmpId = null; // track which employee's payslip is open

  function openPayslip(payrollId) {
    const content = document.getElementById('payslip-content');
    content.innerHTML = '<div class="profile-loading">Loading payslip…</div>';
    openModal('modalPayslip');
    fetchPayslip('payroll/get_payslip.php?payroll_id=' + payrollId);
  }

  function openPayslipByEmployee(empId, month) {
    const content = document.getElementById('payslip-content');
    content.innerHTML = '<div class="profile-loading">Loading payslip…</div>';
    openModal('modalPayslip');
    fetchPayslip('payroll/get_payslip.php?employee_id=' + empId + '&month=' + month);
  }

  function changePayslipMonth(selectEl) {
    if (!_payslipEmpId) return;
    const content = document.getElementById('payslip-content');
    content.innerHTML = '<div class="profile-loading">Loading payslip…</div>';
    fetchPayslip('payroll/get_payslip.php?employee_id=' + _payslipEmpId + '&month=' + selectEl.value);
  }

  function fetchPayslip(url) {
    const content = document.getElementById('payslip-content');

    fetch(url)
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          content.innerHTML = '<div class="profile-empty">⚠️ ' + data.error + '</div>';
          return;
        }

        _payslipEmpId = data.employee_id;

        const picHtml = data.profile_picture
          ? `<img src="uploads/${data.profile_picture}" class="payslip-emp-pic">`
          : `<div class="payslip-emp-pic" style="background:#e2e8f0;"></div>`;

        const statusClass = { Present: 'day-present', Absent: 'day-absent', Late: 'day-late', 'Half-Day': 'day-halfday' };
        const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];

        // Build month dropdown
        let monthOptions = '';
        if (data.available_months && data.available_months.length > 0) {
          data.available_months.forEach(m => {
            const [y, mm] = m.split('-');
            const name = monthNames[parseInt(mm, 10) - 1] + ' ' + y;
            monthOptions += `<option value="${m}" ${m === data.selected_month ? 'selected' : ''}>${name}</option>`;
          });
        }

        // Daily rows
        let dailyRows = '';
        if (data.daily_breakdown && data.daily_breakdown.length > 0) {
          data.daily_breakdown.forEach((d, i) => {
            const dateObj = new Date(d.date + 'T00:00:00');
            const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'short' });
            const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            dailyRows += `<tr>
              <td style="color:#64748b;font-size:12px;">${i + 1}</td>
              <td>${dayName}, ${dateStr}</td>
              <td class="${statusClass[d.status] || ''}">${d.status}</td>
              <td>${d.hours} hrs</td>
              <td>${fmt$(d.daily_rate)}</td>
              <td style="font-weight:600;color:${d.earned > 0 ? '#16a34a' : '#dc2626'}">${fmt$(d.earned)}</td>
            </tr>`;
          });
        } else {
          dailyRows = '<tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">No attendance records for this period</td></tr>';
        }

        // Payment status
        let paidHtml = '';
        if (data.is_saved && data.is_paid) {
          paidHtml = `<div style="display:flex;align-items:center;gap:8px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;margin-bottom:16px;">
            <span style="font-size:20px;">✅</span>
            <div>
              <div style="color:#16a34a;font-weight:700;font-size:14px;">Salary Paid</div>
              <div style="color:#065f46;font-size:12px;">Paid on ${data.paid_date}</div>
            </div>
          </div>`;
        } else if (data.is_saved && !data.is_paid) {
          paidHtml = `<div id="payslip-paid-area" style="display:flex;align-items:center;justify-content:space-between;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:10px 16px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="font-size:20px;">⏳</span>
              <div>
                <div style="color:#92400e;font-weight:700;font-size:14px;">Not Yet Paid</div>
                <div style="color:#78350f;font-size:12px;">Click the button when salary has been given</div>
              </div>
            </div>
            <button onclick="markAsPaid(${data.payroll_id})" style="background:#16a34a;color:white;border:none;padding:8px 16px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:all 0.2s;">💸 Mark as Paid</button>
          </div>`;
        }

        let html = `
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:12px;">
              ${picHtml}
              <div>
                <div class="payslip-emp-name">${data.employee_name}</div>
                <div class="payslip-emp-pos">${data.position}</div>
              </div>
            </div>
            <div>
              <select onchange="changePayslipMonth(this)" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;font-size:13px;cursor:pointer;outline:none;background:white;font-family:inherit;">
                ${monthOptions}
              </select>
            </div>
          </div>

          ${paidHtml}

          <div class="payslip-info-grid">
            <div class="payslip-info-card">
              <div class="label">Daily Rate</div>
              <div class="value blue">${fmt$(data.daily_rate)}</div>
              <div style="font-size:11px;color:#94a3b8;margin-top:2px;">${data.hours_per_day} hrs/day · ${fmt$(data.hourly_rate)}/hr</div>
            </div>
            <div class="payslip-info-card">
              <div class="label">Days Worked</div>
              <div class="value">${data.total_days_worked}</div>
              <div style="font-size:11px;color:#94a3b8;margin-top:2px;">${data.total_hours_worked} total hrs</div>
            </div>
            <div class="payslip-info-card">
              <div class="label">Net Pay</div>
              <div class="value green">${fmt$(data.net_pay)}</div>
            </div>
          </div>

          <div class="payslip-info-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="payslip-info-card">
              <div class="label">Pay Period</div>
              <div class="value" style="font-size:14px;">${data.period_start} to ${data.period_end}</div>
            </div>
            <div class="payslip-info-card">
              <div class="label">Attendance Summary</div>
              <div style="font-size:13px;margin-top:4px;display:flex;gap:10px;flex-wrap:wrap;">
                <span style="color:#16a34a;font-weight:600;">✓ ${data.present_days} Present</span>
                <span style="color:#dc2626;font-weight:600;">✗ ${data.absent_days} Absent</span>
                <span style="color:#d97706;font-weight:600;">⏰ ${data.late_days} Late</span>
                <span style="color:#2563eb;font-weight:600;">½ ${data.half_days} Half-Day</span>
              </div>
            </div>
          </div>

          <div class="payslip-section-title">
            <span>📅 Daily Salary Breakdown</span>
            <span style="font-size:12px;color:#64748b;font-weight:500;">${data.daily_breakdown ? data.daily_breakdown.length : 0} days recorded</span>
          </div>
          <div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:8px;">
            <table class="payslip-daily-table">
              <thead><tr><th>#</th><th>Date</th><th>Status</th><th>Hours</th><th>Rate/Day</th><th>Earned</th></tr></thead>
              <tbody>${dailyRows}</tbody>
            </table>
          </div>

          <div class="payslip-section-title"><span>💰 Earnings & Deductions</span></div>
          <div class="payslip-ded-row">
            <span class="payslip-ded-label">Gross Pay (${data.total_days_worked} days × ${fmt$(data.daily_rate)})</span>
            <span style="color:#0f172a;font-weight:700;">${fmt$(data.gross_pay)}</span>
          </div>
          <div class="payslip-ded-row">
            <span class="payslip-ded-label">SSS <span class="payslip-badge">Gov't</span></span>
            <span class="payslip-ded-amount">- ${fmt$(data.sss)}</span>
          </div>
          <div class="payslip-ded-row">
            <span class="payslip-ded-label">PhilHealth <span class="payslip-badge">Gov't</span></span>
            <span class="payslip-ded-amount">- ${fmt$(data.philhealth)}</span>
          </div>
          <div class="payslip-ded-row">
            <span class="payslip-ded-label">Pag-IBIG <span class="payslip-badge">Gov't</span></span>
            <span class="payslip-ded-amount">- ${fmt$(data.pagibig)}</span>
          </div>
          <div class="payslip-total-row">
            <span>Total Deductions</span>
            <span style="color:#dc2626;">- ${fmt$(data.total_deductions)}</span>
          </div>
          <div class="payslip-total-row" style="border-top:3px solid #16a34a;">
            <span style="color:#16a34a;">NET PAY</span>
            <span class="net">${fmt$(data.net_pay)}</span>
          </div>

          <div style="text-align:center;margin-top:24px;">
            <button class="payslip-print-btn" onclick="window.print()">🖨️ Print Payslip</button>
          </div>
        `;

        content.innerHTML = html;
      })
      .catch(() => {
        content.innerHTML = '<div class="profile-empty">⚠️ Could not load payslip. Please try again.</div>';
      });
  }

  function markAsPaid(payrollId) {
    if (!confirm('Mark this payroll as paid? This confirms salary has been given to the employee.')) return;

    const area = document.getElementById('payslip-paid-area');
    if (area) area.innerHTML = '<span style="color:#64748b;">Processing...</span>';

    const formData = new FormData();
    formData.append('payroll_id', payrollId);

    fetch('payroll/mark_paid.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          if (area) {
            area.style.background = '#f0fdf4';
            area.style.borderColor = '#bbf7d0';
            area.innerHTML = `
              <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:20px;">✅</span>
                <div>
                  <div style="color:#16a34a;font-weight:700;font-size:14px;">Salary Paid</div>
                  <div style="color:#065f46;font-size:12px;">Paid on ${data.paid_date}</div>
                </div>
              </div>`;
          }
        } else {
          alert('Error: ' + (data.error || 'Could not mark as paid'));
        }
      })
      .catch(() => alert('Network error. Please try again.'));
  }


 function openEditProduct(data) {
  document.getElementById('ep_product_id').value = data.product_id;
  document.getElementById('ep_product_name').value = data.product_name;
  document.getElementById('ep_product_variant').value = data.product_variant;
  document.getElementById('ep_price').value = parseInt(data.price);
  document.getElementById('ep_stock_quantity').value = parseInt(data.stock_quantity);

  document.getElementById('editProductModal').classList.add('open');
}

  /* ---- Search / Filter ---- */
  function filterTable(inputId, tableId, countId) {
    const q     = document.getElementById(inputId).value.toLowerCase().trim();
    const tbody = document.querySelector('#' + tableId + ' tbody');
    const rows  = tbody.querySelectorAll('tr[data-name]');
    let visible = 0;
    rows.forEach(row => {
      const match = !q || row.dataset.name.includes(q);
      row.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    const countEl = document.getElementById(countId);
    if (countEl) countEl.textContent = q ? `${visible} result${visible !== 1 ? 's' : ''}` : '';
  }

  /* ---- Employee Profile Modal ---- */
  // PHP data baked in for quick lookup
  const empData = <?php
    $allEmps = $pdo->query("
      SELECT e.employee_id, e.first_name, e.last_name, e.gender, e.profile_picture,
             e.email, e.phone_number, e.hire_date, e.date_of_birth, e.address,
             p.position_title, p.base_salary
      FROM employees e
      JOIN positions p ON e.position_id = p.position_id
      WHERE e.is_archived = 0
    ")->fetchAll(PDO::FETCH_ASSOC);
    $empMap = [];
    foreach ($allEmps as $e) $empMap[$e['employee_id']] = $e;
    echo json_encode($empMap);
  ?>;

  const attData = <?php
    $allAtt = $pdo->query("
      SELECT employee_id, attendance_id, date, status, hours_worked
      FROM attendance ORDER BY date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    $attMap = [];
    foreach ($allAtt as $a) {
      $attMap[$a['employee_id']][] = $a;
    }
    echo json_encode($attMap);
  ?>;

  const payData = <?php
    $allPay = $pdo->query("
      SELECT employee_id, payroll_id, gross_pay, net_pay,
             sss_contribution, philhealth_contribution, pagibig_contribution,
             attendance_deduction, payment_date
      FROM payroll ORDER BY payment_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    $payMap = [];
    foreach ($allPay as $p) {
      $payMap[$p['employee_id']][] = $p;
    }
    echo json_encode($payMap);
  ?>;

  function renderAttendanceTab(empId, selectedMonth) {
    const atts = attData[empId] || [];
    
    // Get unique months from records
    const monthsSet = new Set();
    const now = new Date();
    const currentMonthStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    monthsSet.add(currentMonthStr); // Always include current month
    
    atts.forEach(a => {
        if (a.date) {
            monthsSet.add(a.date.substring(0, 7)); // YYYY-MM
        }
    });
    
    // Sort months descending
    const sortedMonths = Array.from(monthsSet).sort().reverse();
    
    if (!selectedMonth) {
        selectedMonth = currentMonthStr;
    }
    
    const [selYear, selMonth] = selectedMonth.split('-');
    
    let presentCount = 0, absentCount = 0, lateCount = 0;
    const filteredAtts = [];
    
    atts.forEach(a => {
      if (a.date && a.date.startsWith(selectedMonth)) {
        filteredAtts.push(a);
        if (a.status === 'Present') presentCount++;
        else if (a.status === 'Absent') absentCount++;
        else if (a.status === 'Late') lateCount++;
      }
    });

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    
    let optionsHtml = '';
    sortedMonths.forEach(m => {
        const [y, mm] = m.split('-');
        const name = monthNames[parseInt(mm, 10) - 1] + ' ' + y;
        optionsHtml += `<option value="${m}" ${m === selectedMonth ? 'selected' : ''}>${name}</option>`;
    });

    let attHtml = `
      <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin-top: 0; margin-bottom: 8px; color: #333; font-size: 14px;">Summary for ${monthNames[parseInt(selMonth, 10) - 1]} ${selYear}</h4>
            <div style="display: flex; gap: 15px; font-size: 13px;">
              <div style="color: #27ae60;"><strong>Present:</strong> ${presentCount}</div>
              <div style="color: #e74c3c;"><strong>Absent:</strong> ${absentCount}</div>
              <div style="color: #f39c12;"><strong>Late:</strong> ${lateCount}</div>
            </div>
        </div>
        <div>
            <select onchange="renderAttendanceTab(${empId}, this.value)" style="padding: 5px 10px; border-radius: 4px; border: 1px solid #ccc; font-size: 13px; cursor: pointer; outline: none; background: white;">
                ${optionsHtml}
            </select>
        </div>
      </div>
    `;

    if (filteredAtts.length) {
      const statusClass = { Present:'s-present', Absent:'s-absent', Late:'s-late', 'Half-Day':'s-halfday' };
      attHtml += `<table class="profile-mini-table">
        <thead><tr><th>Date</th><th>Status</th><th>Hours</th></tr></thead><tbody>`;
      filteredAtts.slice(0, 50).forEach(a => {
        const cls = statusClass[a.status] || '';
        attHtml += `<tr>
          <td>${a.date}</td>
          <td class="${cls}">${a.status}</td>
          <td>${a.hours_worked} hrs</td>
        </tr>`;
      });
      attHtml += '</tbody></table>';
      if (filteredAtts.length > 50) attHtml += `<p style="font-size:12px;color:#888;margin-top:8px;">Showing latest 50 of ${filteredAtts.length} records.</p>`;
    } else {
      attHtml += '<div class="profile-empty">No attendance records found for this month.</div>';
    }
    document.getElementById('prof-att-body').innerHTML = attHtml;
  }

  function setupProfileData(empId) {
    const emp = empData[empId];
    if (!emp) return null;

    document.querySelectorAll('.att-quick-emp-id').forEach(el => el.value = empId);

    const avatarDiv = document.getElementById('prof-avatar');
    if (emp.profile_picture) {
        avatarDiv.innerHTML = `<img src="uploads/${emp.profile_picture}" style="width:100%;height:100%;object-fit:cover;">`;
    } else {
        avatarDiv.innerHTML = '';
    }

    document.getElementById('prof-name').textContent    = emp.first_name + ' ' + emp.last_name;
    document.getElementById('prof-pos').textContent     = emp.position_title;
    document.getElementById('prof-email').textContent   = emp.email || '—';
    document.getElementById('prof-phone').textContent   = emp.phone_number || '—';
    document.getElementById('prof-gender').textContent  = emp.gender || '—';
    document.getElementById('prof-dob').textContent     = emp.date_of_birth || '—';
    document.getElementById('prof-hire').textContent    = emp.hire_date || '—';
    document.getElementById('prof-address').textContent = emp.address || '—';

    renderAttendanceTab(empId);
    return emp;
  }

  function showAllTabs() {
    const tabs = document.querySelectorAll('.profile-tab');
    tabs.forEach(t => t.style.display = '');
  }

  function openEmpProfile(empId) {
    const emp = setupProfileData(empId);
    if (!emp) return;

    // Show all tabs (Info, Attendance, Payslip)
    showAllTabs();

    // Payroll tab
    const pays = payData[empId] || [];
    let payHtml = '';

    const now2 = new Date();
    const curMonth = now2.getFullYear() + '-' + String(now2.getMonth() + 1).padStart(2, '0');
    payHtml += `<div style="display:flex;justify-content:flex-end;margin-bottom:10px;">
      <button class="btn-profile-action" style="background:#4f46e5;color:white;" onclick="closeModal('modalEmpProfile');openPayslipByEmployee(${empId},'${curMonth}')">📄 View Payslip</button>
    </div>`;

    if (pays.length) {
      payHtml += `<table class="profile-mini-table">
        <thead><tr><th>Payroll ID</th><th>Payment Date</th><th>Gross Pay</th><th>Net Pay</th><th></th></tr></thead><tbody>`;
      pays.forEach(p => {
        const gross = parseFloat(p.gross_pay).toLocaleString('en-PH', {minimumFractionDigits:2});
        const net   = parseFloat(p.net_pay).toLocaleString('en-PH', {minimumFractionDigits:2});
        payHtml += `<tr style="cursor:pointer;" onclick="closeModal('modalEmpProfile');openPayslip(${p.payroll_id})">
          <td style="color:#888;font-size:12px;">#${p.payroll_id}</td>
          <td>${p.payment_date}</td>
          <td>₱${gross}</td>
          <td style="color:#27ae60;font-weight:600;">₱${net}</td>
          <td><span style="color:#4f46e5;font-size:12px;font-weight:600;">View →</span></td>
        </tr>`;
      });
      payHtml += '</tbody></table>';
    } else {
      payHtml += '<div class="profile-empty">No payroll records found.</div>';
    }
    document.getElementById('prof-pay-body').innerHTML = payHtml;

    switchProfileTab('tab-info');
    openModal('modalEmpProfile');
  }

  function openAttendanceProfile(empId) {
    const emp = setupProfileData(empId);
    if (!emp) return;

    // Hide Info and Payslip tabs — only show Attendance
    const tabs = document.querySelectorAll('.profile-tab');
    tabs.forEach((t, i) => {
      // index 0 = Info, 1 = Attendance, 2 = Payslip
      t.style.display = (i === 1) ? '' : 'none';
    });

    switchProfileTab('tab-attendance');
    openModal('modalEmpProfile');
  }

  function initAttendanceFilter() {
    const monthsSet = new Set();
    const now = new Date();
    const currentMonthStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    monthsSet.add(currentMonthStr);
    
    for (let empId in attData) {
        attData[empId].forEach(a => {
            if (a.date) monthsSet.add(a.date.substring(0, 7));
        });
    }
    
    const sortedMonths = Array.from(monthsSet).sort().reverse();
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    
    const select = document.getElementById('attendance-month-filter');
    if (!select) return;
    
    let optionsHtml = '';
    sortedMonths.forEach(m => {
        const [y, mm] = m.split('-');
        const name = monthNames[parseInt(mm, 10) - 1] + ' ' + y;
        optionsHtml += `<option value="${m}">${name}</option>`;
    });
    select.innerHTML = optionsHtml;
    select.value = currentMonthStr;
    
    renderAttendanceTable();
  }

  function renderAttendanceTable() {
    const select = document.getElementById('attendance-month-filter');
    if (!select) return;
    const selectedMonth = select.value;
    
    const tbody = document.getElementById('attendance-overview-body');
    if (!tbody) return;
    let html = '';
    
    let hasData = false;
    const emps = Object.values(empData).sort((a, b) => a.first_name.localeCompare(b.first_name));
    
    emps.forEach(emp => {
       const empId = emp.employee_id;
       const atts = attData[empId] || [];
       
       let present = 0, absent = 0, late = 0, halfday = 0;
       
       atts.forEach(a => {
         if (a.date && a.date.startsWith(selectedMonth)) {
            if (a.status === 'Present') present++;
            else if (a.status === 'Absent') absent++;
            else if (a.status === 'Late') late++;
            else if (a.status === 'Half-Day') halfday++;
         }
       });
       
       const lateAbs = Math.floor(late / 3);
       const totalAbs = absent + lateAbs;
       
       html += `<tr class="clickable-row" onclick="openAttendanceProfile(${empId})">
         <td>
            <div style="display: flex; align-items: center; gap: 12px;">
                ${emp.profile_picture ? `<img src="uploads/${emp.profile_picture}" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">` : `<div style="width:32px; height:32px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center;"></div>`}
                ${emp.first_name} ${emp.last_name}
            </div>
         </td>
         <td style="color:#27ae60;font-weight:600;">${present}</td>
         <td style="color:#e74c3c;font-weight:600;">${absent}</td>
         <td style="color:#f39c12;font-weight:600;">${late}</td>
         <td style="color:#3498db;font-weight:600;">${halfday}</td>
         <td style="color:#c0392b;font-weight:bold;">${totalAbs}</td>
       </tr>`;
       hasData = true;
    });
    
    if (!hasData) {
       html = '<tr class="empty"><td colspan="6">No employees found.</td></tr>';
    }
    
    tbody.innerHTML = html;
  }

  function switchProfileTab(tabId) {
    document.querySelectorAll('.profile-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    const idx = ['tab-info','tab-attendance','tab-payroll'].indexOf(tabId);
    document.querySelectorAll('.profile-tab')[idx].classList.add('active');
  }


  const sidebarLinks = document.querySelectorAll('.sidebar-link');
  const sections     = document.querySelectorAll('.section[id]');

  sidebarLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const target = this.dataset.target;

      // Update active link
      sidebarLinks.forEach(l => l.classList.remove('active'));
      this.classList.add('active');

      // Show target section, hide others
      sections.forEach(sec => {
        sec.style.display = sec.id === target ? 'block' : 'none';
      });
    });
  });

  function filterPayrollTable() {
    const selectedMonth = document.getElementById('payroll-month-filter').value;
    const rows = document.querySelectorAll('.payroll-row');
    
    let hasVisible = false;
    rows.forEach(row => {
        const date = row.dataset.date || '';
        if (selectedMonth === 'all' || date.startsWith(selectedMonth)) {
            row.style.display = '';
            hasVisible = true;
        } else {
            row.style.display = 'none';
        }
    });
    
    const emptyRow = document.getElementById('payroll-empty-row');
    if (emptyRow) {
        emptyRow.style.display = hasVisible ? 'none' : '';
    }
  }



  // On page load, check URL hash or default to employees
  window.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.replace('#', '');
    const validTargets = ['section-employees','section-archived','section-products','section-payroll','section-deductions','section-attendance','section-reservations'];
    const target = validTargets.includes(hash) ? hash : 'section-employees';

    sidebarLinks.forEach(l => {
      l.classList.toggle('active', l.dataset.target === target);
    });
    sections.forEach(sec => {
      sec.style.display = sec.id === target ? 'block' : 'none';
    });
    
    initAttendanceFilter();
  });

</script>
</body>
</html>