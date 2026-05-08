<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isSuperadmin = !empty($_SESSION['superadmin_logged_in']) && $_SESSION['superadmin_logged_in'] === true;

if (!$isAdmin && !$isSuperadmin) {
    header("Location: homepage.php");
    exit;
}
?>