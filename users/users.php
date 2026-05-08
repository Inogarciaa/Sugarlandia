<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];
$role = $_POST['role'];
$created_at = $_POST['created_at'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Book</title>
<style>
body { font-size: 18px; color: #000000; }
label, input, button { font-size: 18px; }
</style>
</head>
<body>
<h1>Users Login</h1>
<form method="POST" action="users_data.php">
<label>name:</label><input type="text" name="name" required><br><br>
<label>email:</label><input type="text" name="email" required><br><br>
<label>password:</label><input type="password" name="password" required><br><br>
<label>role:</label>
<select name="role" required>
    <option value="admin">Admin</option>
    <option value="user">User</option>
    <option value="employee">Employee</option>
</select><br><br>
<label>created_at:</label><input type="date" name="created_at" required><br><br>


<button type="submit">Add</button>
</form>
</body>
</html>