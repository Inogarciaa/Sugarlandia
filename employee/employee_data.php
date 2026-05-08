<?php
session_start();
require '../auth.php'; // 🔒 Admin guard
require '../config.php';

$first_name    = trim($_POST['first_name']);
$last_name     = trim($_POST['last_name']);
$date_of_birth = trim($_POST['date_of_birth']);
$gender        = trim($_POST['gender']);
$address       = trim($_POST['address']);
$phone_number  = trim($_POST['phone_number']);
$email         = trim($_POST['email']);
$hire_date     = trim($_POST['hire_date']);
$position_id   = (int)$_POST['position_id'];

if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($gender) ||
    empty($address) || empty($phone_number) || empty($email) || empty($hire_date) || empty($position_id)) {
    die("<p style='color:red;font-family:Arial'>❌ All fields must be filled up. <a href='employee.php'>Go back</a></p>");
}

$profile_picture = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['profile_picture']['tmp_name'];
    $name = basename($_FILES['profile_picture']['name']);
    
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $new_name = uniqid('emp_') . '.' . $ext;
    
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
        $profile_picture = $new_name;
    }
}

if (!$profile_picture) {
    die("<p style='color:red;font-family:Arial'>❌ Profile picture is required. <a href='employee.php'>Go back</a></p>");
}

try {
    $sql = "INSERT INTO employees
                (first_name, last_name, date_of_birth, gender, address, phone_number, email, hire_date, position_id, profile_picture)
            VALUES
                (:first_name, :last_name, :date_of_birth, :gender, :address, :phone_number, :email, :hire_date, :position_id, :profile_picture)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':first_name'      => $first_name,
        ':last_name'       => $last_name,
        ':date_of_birth'   => $date_of_birth,
        ':gender'          => $gender,
        ':address'         => $address,
        ':phone_number'    => $phone_number,
        ':email'           => $email,
        ':hire_date'       => $hire_date,
        ':position_id'     => $position_id,
        ':profile_picture' => $profile_picture,
    ]);

    $newId = $pdo->lastInsertId();
    header("Location: ../index.php?success=Employee+registered+successfully+(ID:+$newId)");
    exit;

} catch (PDOException $e) {
    echo "<p style='color:red;font-family:Arial'>❌ Error: " . htmlspecialchars($e->getMessage()) . " <a href='employee.php'>Go back</a></p>";
}
?>