<?php
// connections/db.php

$host     = "localhost";
$dbname   = "thongpoon_rice";
$username = "root";
$password = "";          // XAMPP ค่าเริ่มต้นไม่มี password

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    // ตั้งค่าให้แสดง error ชัดเจน (ใช้ตอน dev เท่านั้น)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("❌ เชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}
?>