<?php
session_start();
require '../connections/db.php';

header('Content-Type: application/json');

// เช็คว่า login แล้ว
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่ได้ login']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($data['order_id'] ?? 0);
$status   = $data['status'] ?? '';

// เช็คค่าที่รับมา
if ($order_id <= 0 || !in_array($status, ['paid', 'cancelled'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET payment_status = :status 
        WHERE order_id = :order_id
    ");
    $stmt->execute([
        ':status'   => $status,
        ':order_id' => $order_id
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
