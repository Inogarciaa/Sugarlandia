<?php
require __DIR__ . "/config.php";  // ← also fixed the slash from before

function registerUser($pdo, $username, $password) {
    try {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, password) 
            VALUES (:name, :password)
        ");
        
        return $stmt->execute([
            ':name'     => $username,  // ← was :username
            ':password' => $hashed
        ]);

    } catch (PDOException $e) {
        echo "Register Error: " . $e->getMessage(); 
        return false;
    }
}

function loginUser($pdo, $username, $password) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE name = ?");  // ← was username
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;

    } catch (PDOException $e) {
        echo "Login Error: " . $e->getMessage(); 
        return false;
    }
}

function fetchUsers($pdo) {
    try {
        $sql = "SELECT * FROM users";
        $stmt = $pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Fetch Error: " . $e->getMessage();
        return [];
    }
}

function logoutUser($redirect = true) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];
    session_destroy();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    if ($redirect) {
        header("Location: ./index.php");
        exit;
    }
}
?>