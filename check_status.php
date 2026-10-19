<?php
require '../connections/db.php';

header('Content-Type: application/json');

$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['status' => 'error']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT payment_status FROM orders WHERE order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if ($order) {
    echo json_encode(['status' => $order['payment_status']]);
} else {
    echo json_encode(['status' => 'error']);
}
?>
