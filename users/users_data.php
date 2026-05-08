<?php
require '../config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name   = trim($_POST['name']);
    $password   = trim($_POST['password']);
    $email        = trim($_POST['email']);
    $role        = trim($_POST['role']);
    $created_at   = trim($_POST['created_at']);

    if (empty($name) || empty($password)  || empty($email) || empty($created_at)|| empty($role)) {
        die("<p style='color:red;font-family:Arial'>❌ All fields must be filled up. <a href='users.php'>Go back</a></p>");
    } else {
        try {
            $sql = "INSERT INTO users (name, email, password, role, created_at)
                    VALUES (:name, :email, :password, :role, :created_at)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'       => $name,
                ':email'      => $email,
                ':password'   => password_hash($password, PASSWORD_BCRYPT),
                ':role'       => $role,
                ':created_at' => $created_at,
            ]);
            $newId = $pdo->lastInsertId();
            header("Location: ../index.php?success=" . urlencode("User created successfully (ID: $newId)"));
            exit;
        } catch (PDOException $e) {
            echo "<p style='color:red;font-family:Arial'>❌ Error: " . htmlspecialchars($e->getMessage()) . " <a href='users.php'>Go back</a></p>";
        }
    }
}
?>