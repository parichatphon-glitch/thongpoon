<?php
session_start();
require '../connections/db.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// เช็คว่ากรอกครบมั้ย
if (empty($username) || empty($password)) {
    header("Location: login.php?error=empty");
    exit;
}

// ค้นหา user จากฐานข้อมูล
$stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE username = :username 
    AND password = :password
    LIMIT 5
");
$stmt->execute([
    ':username' => $username,
    ':password' => $password
]);
$user = $stmt->fetch();

if ($user) {
    // login สำเร็จ เก็บข้อมูลไว้ใน session
    $_SESSION['user'] = [
        'user_id'   => $user['user_id'],
        'username'  => $user['username'],
        'firstname' => $user['firstname'],
        'lastname'  => $user['lastname'],
        'role'      => $user['role']
    ];
    header("Location: dashboard.php");
    exit;
} else {
    // login ไม่สำเร็จ
    header("Location: login.php?error=wrong");
    exit;
}
?>