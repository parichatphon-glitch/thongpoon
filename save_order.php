<?php
session_start();
require '../connections/db.php';

header('Content-Type: application/json');

// รับข้อมูล JSON จาก JavaScript
$data       = json_decode(file_get_contents('php://input'), true);
$table_id   = (int)($data['table_id']   ?? 0);
$pay_method = $data['pay_method'] ?? '';
$dine_type  = $data['dine_type'] ?? 'dine_in'; // เพิ่มบรรทัดนี้
$pro_id = $data['pro_id'] ?? null;
$cart       = $data['cart']       ?? [];

// เช็คข้อมูลเบื้องต้น
if ($table_id <= 0 || empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
    exit;
}

try {
    // คำนวณราคารวม
   $takeaway_fee = (float)($data['takeaway_fee'] ?? 0);
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
}
$total += $takeaway_fee;

    // บันทึก orders หลัก
$stmt = $pdo->prepare("
    INSERT INTO orders 
        (table_id, pay_method, payment_status, order_type, pro_id, total_price) 
    VALUES 
        (:table_id, :pay_method, 'pending', :dine_type, :pro_id, :total_price)
");
$stmt->execute([
    ':table_id'    => $table_id,
    ':pay_method'  => $pay_method,
    ':dine_type'   => $dine_type,
    ':pro_id'      => $pro_id,
    ':total_price' => $total
]);

    $order_id = $pdo->lastInsertId();

    // บันทึก order_detail ทีละรายการ
    $stmt2 = $pdo->prepare("
        INSERT INTO order_detail 
            (order_id, menu_id, quantity, price, remark) 
        VALUES 
            (:order_id, :menu_id, :quantity, :price, :remark)
    ");

    foreach ($cart as $item) {
    // ตัด _free, _S, _M ออก
    $menu_id = preg_replace('/_(free|S|M)$/', '', $item['id']);

    
    $stmt2->execute([
        ':order_id' => $order_id,
        ':menu_id'  => $menu_id,
        ':quantity' => (int)$item['qty'],
        ':price'    => (float)$item['price'],
        ':remark'   => $item['remark'] ?? ''
        ]);
    }

    // ตอบกลับว่าสำเร็จ พร้อมส่ง order_id กลับไป
    echo json_encode([
        'success'  => true,
        'order_id' => $order_id
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>