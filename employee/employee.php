<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Registration</title>
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
      border: 1px solid var(--border-color); box-sizing: border-box;
    }
    h2 { font-size: 24px; font-weight: 700; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); margin-top: 0; }
    label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-top: 20px; margin-bottom: 6px; }
    input:not([type="submit"]), select {
      width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px;
      font-size: 14px; box-sizing: border-box; outline: none; transition: all 0.2s; font-family: inherit; color: var(--text-main);
    }
    input:focus:not([type="submit"]), select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
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
  </style>
</head>
<body>
  <a class="back" href="../index.php">← Back to Dashboard</a>
  <form action="employee_data.php" method="POST" enctype="multipart/form-data">
    <h2>Employee Registration</h2>
    <label>First Name:</label>
    <input type="text" name="first_name" required>

    <label>Last Name:</label>
    <input type="text" name="last_name" required>

    <label>Date of Birth:</label>
    <input type="date" name="date_of_birth" required>

    <label>Gender:</label>
    <select name="gender" required>
      <option value="">-- Select Gender --</option>
      <option value="Male">Male</option>
      <option value="Female">Female</option>
      <option value="Other">Other</option>
    </select>

    <label>Address:</label>
    <input type="text" name="address" required>

    <label>Phone Number:</label>
    <input type="text" name="phone_number" required>

    <label>Email:</label>
    <input type="email" name="email" required>

    <label>Hire Date:</label>
    <input type="date" name="hire_date" required>

    <label>Position ID:</label>
     <select name="position_id" required>
      <option value="">-- Select Position --</option>
      <option value="1">Manager</option>
      <option value="2">Pastry Chef</option>
      <option value="3">Cashier</option>
      <option value="4">Staff</option>
    </select>

    <label>Profile Picture:</label>
    <input id="imageInput" type="file" name="profile_picture" accept="image/*" required style="padding: 10px; background: #fff;">
    <p id="message" style="margin-top:8px; font-size:13px;"></p>
    <div id="preview" style="margin-top:10px; margin-bottom: 20px;"></div>

    <input type="submit" value="Register Employee">
  </form>
  <script>
    const input = document.getElementById("imageInput");
    const message = document.getElementById("message");
    const preview = document.getElementById("preview");

    input.addEventListener("change", function () {
        const file = this.files[0];
        message.textContent = "";
        preview.innerHTML = "";

        if (!file) return;

        if (!file.type.startsWith("image/")) {
            message.textContent = "❌ Invalid file type. Only images allowed.";
            message.style.color = "red";
            this.value = "";
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            message.textContent = "❌ File too large (max 5MB).";
            message.style.color = "red";
            this.value = "";
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<img src="${e.target.result}" style="width:120px;height:120px;object-fit:cover;border-radius:50%;box-shadow:0 2px 4px rgba(0,0,0,0.1);">`;
            message.textContent = "✅ Image valid.";
            message.style.color = "green";
        };
        reader.readAsDataURL(file);
    });
  </script> 
</body>
</html>