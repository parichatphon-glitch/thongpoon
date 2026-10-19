<?php
session_start();
require '../connections/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่ได้ login']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    // ===== เพิ่มเมนู =====
    if ($action === 'add') {
        // เช็คว่า menu_id ซ้ำหรือเปล่า
        $check = $pdo->prepare("SELECT menu_id FROM menu WHERE menu_id = ?");
        $check->execute([$data['menu_id']]);
        if ($check->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'รหัสเมนู ' . $data['menu_id'] . ' มีอยู่แล้ว'
            ]);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO menu 
                (menu_id, menu_name, sale_price, cat_id, menu_picture, is_available)
            VALUES 
                (:menu_id, :menu_name, :sale_price, :cat_id, :menu_picture, :is_available)
        ");
        $stmt->execute([
            ':menu_id'      => $data['menu_id'],
            ':menu_name'    => $data['menu_name'],
            ':sale_price'   => $data['sale_price'],
            ':cat_id'       => $data['cat_id'],
            ':menu_picture' => $data['menu_picture'] ?? '',
            ':is_available' => $data['is_available']
        ]);
        echo json_encode(['success' => true]);
    }

    // ===== แก้ไขเมนู =====
    elseif ($action === 'edit') {
        $stmt = $pdo->prepare("
            UPDATE menu SET
                menu_name    = :menu_name,
                sale_price   = :sale_price,
                cat_id       = :cat_id,
                menu_picture = :menu_picture,
                is_available = :is_available
            WHERE menu_id = :menu_id
        ");
        $stmt->execute([
            ':menu_name'    => $data['menu_name'],
            ':sale_price'   => $data['sale_price'],
            ':cat_id'       => $data['cat_id'],
            ':menu_picture' => $data['menu_picture'] ?? '',
            ':is_available' => $data['is_available'],
            ':menu_id'      => $data['menu_id']
        ]);
        echo json_encode(['success' => true]);
    }

    // ===== ลบเมนู =====
    elseif ($action === 'delete') {
        // เช็คก่อนว่าเมนูนี้มีใน order_detail หรือเปล่า
        $check = $pdo->prepare("
            SELECT COUNT(*) as cnt FROM order_detail WHERE menu_id = ?
        ");
        $check->execute([$data['menu_id']]);
        $result = $check->fetch();

        if ($result['cnt'] > 0) {
            // ถ้ามีออเดอร์แล้ว ให้ปิดแทนการลบ
            $stmt = $pdo->prepare("
                UPDATE menu SET is_available = 0 WHERE menu_id = ?
            ");
            $stmt->execute([$data['menu_id']]);
            echo json_encode([
                'success' => true,
                'message' => 'เมนูนี้มีออเดอร์แล้ว จึงปิดการขายแทนการลบ'
            ]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM menu WHERE menu_id = ?");
            $stmt->execute([$data['menu_id']]);
            echo json_encode(['success' => true]);
        }
    }

    else {
        echo json_encode(['success' => false, 'message' => 'action ไม่ถูกต้อง']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
