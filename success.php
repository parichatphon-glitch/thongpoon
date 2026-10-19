<?php
session_start();
require '../connections/db.php';

$order_id = (int)($_GET['order_id'] ?? 0);
$table_id = $_SESSION['table_id'] ?? 0;

if ($order_id <= 0) {
    header("Location: index.php?table=" . $table_id);
    exit;
}

// ดึงข้อมูลออเดอร์
$order_stmt = $pdo->prepare("
    SELECT o.*, t.table_name
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.table_id
    WHERE o.order_id = ?
");
$order_stmt->execute([$order_id]);
$order = $order_stmt->fetch();

if (!$order) {
    die("❌ ไม่พบออเดอร์นี้");
}

// ดึงรายละเอียดอาหาร
$details = $pdo->prepare("
    SELECT od.*, m.menu_name
    FROM order_detail od
    JOIN menu m ON od.menu_id = m.menu_id
    WHERE od.order_id = ?
");
$details->execute([$order_id]);
$items = $details->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ - Thongpoon Rice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; font-family: 'Kanit', sans-serif; }

        /* ===== หน้ารอยืนยัน ===== */
        #waiting-screen {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff8f0, #ffe0e0);
        }
        .waiting-box {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 360px;
            width: 100%;
        }
        /* วงกลมหมุน */
        .spinner {
            width: 80px;
            height: 80px;
            border: 6px solid #f0f0f0;
            border-top: 6px solid #e74c3c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0%   { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .waiting-title {
            font-size: 20px;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }
        .waiting-sub { color: #888; font-size: 14px; margin-bottom: 20px; }
        .order-badge {
            background: #fff5f5;
            border-radius: 12px;
            padding: 12px 20px;
            display: inline-block;
            margin-bottom: 16px;
        }
        .order-badge .num {
            font-size: 22px;
            font-weight: 500;
            color: #e74c3c;
        }
        .order-badge .label { font-size: 13px; color: #888; }
        .checking-text {
            font-size: 13px;
            color: #aaa;
            margin-top: 16px;
        }
        .dot-ani::after {
            content: '';
            animation: dots 1.5s infinite;
        }
        @keyframes dots {
            0%   { content: ''; }
            33%  { content: '.'; }
            66%  { content: '..'; }
            100% { content: '...'; }
        }

        /* ===== หน้าใบเสร็จ ===== */
        #receipt-screen { display: none; }
        .action-bar {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .action-bar h5 { color: white; margin: 0; font-weight: 500; }
        .btn-print {
            background: white;
            color: #e74c3c;
            border: none;
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-print:hover { background: #fff5f5; }
        .receipt-wrapper {
            display: flex;
            justify-content: center;
            padding: 24px 16px 40px;
        }
        .receipt {
            background: white;
            width: 100%;
            max-width: 420px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .receipt-header {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: white;
            text-align: center;
            padding: 24px 20px 20px;
        }
        .receipt-header .shop-icon { font-size: 48px; }
        .receipt-header h4 {
            margin: 8px 0 2px;
            font-weight: 500;
            font-size: 20px;
        }
        .receipt-header p { margin: 0; font-size: 13px; opacity: 0.85; }
        .receipt-body { padding: 20px; }
        .status-badge {
            text-align: center;
            background: #d4edda;
            color: #155724;
            border-radius: 10px;
            padding: 10px;
            font-weight: 500;
            font-size: 15px;
            margin-bottom: 18px;
        }
        .order-info {
            display: flex;
            justify-content: space-between;
            background: #fff8f0;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .order-info .info-item { text-align: center; }
        .order-info .info-label { color: #888; font-size: 12px; }
        .order-info .info-value { font-weight: 500; color: #333; margin-top: 2px; }
        .dashed-line {
            border: none;
            border-top: 2px dashed #e0e0e0;
            margin: 16px 0;
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #888;
            font-weight: 500;
            margin-bottom: 8px;
            padding: 0 4px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 8px 4px;
            border-bottom: 1px solid #f5f5f5;
            font-size: 14px;
        }
        .item-row:last-child { border-bottom: none; }
        .item-name { color: #333; flex: 1; }
        .item-qty  { color: #888; width: 40px; text-align: center; }
        .item-price { font-weight: 500; color: #333; width: 70px; text-align: right; }
        .item-remark { font-size: 12px; color: #888; margin-top: 2px; }
        .total-section {
            background: #fff8f0;
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 16px;
        }
        .total-final {
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: 500;
            color: #e74c3c;
            padding-top: 10px;
            border-top: 2px solid #e0e0e0;
        }
        .receipt-footer {
            text-align: center;
            padding: 16px 20px 24px;
            color: #888;
            font-size: 13px;
        }
        .receipt-footer .thank-you {
            font-size: 16px;
            color: #e74c3c;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .btn-order-more {
            display: block;
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: white;
            text-align: center;
            border-radius: 12px;
            padding: 12px;
            margin: 0 20px 20px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
        }
        .btn-order-more:hover { opacity: 0.9; color: white; }

        @media print {
            body { background: white; }
            .action-bar   { display: none !important; }
            .btn-order-more { display: none !important; }
            .receipt-wrapper { padding: 0; }
            .receipt { box-shadow: none; border-radius: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<!-- ===== หน้ารอยืนยัน ===== -->
<div id="waiting-screen">
    <div class="waiting-box">
        <div class="spinner"></div>
        <div class="waiting-title">รอพนักงานยืนยัน</div>
        <div class="waiting-sub">
            พนักงานกำลังตรวจสอบการชำระเงิน<br>กรุณารอสักครู่นะครับ
        </div>
        <div class="order-badge">
            <div class="label">หมายเลขออเดอร์</div>
            <div class="num">#<?= $order_id ?></div>
            <div class="label"><?= htmlspecialchars($order['table_name']) ?></div>
        </div>
        <div class="checking-text">
            กำลังตรวจสอบสถานะ<span class="dot-ani"></span>
        </div>
    </div>
</div>

<!-- ===== หน้าใบเสร็จ ===== -->
<div id="receipt-screen">
    <div class="action-bar">
        <h5>🧾 ใบเสร็จรับเงิน</h5>
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> พิมพ์ / บันทึก PDF
        </button>
    </div>

    <div class="receipt-wrapper">
        <div class="receipt">
            <div class="receipt-header">
<img src="/thongpoonrice/assets/images/logo.png" 
         style="height:70px; width:70px; border-radius:50%; object-fit:cover; margin-right:8px;">                <h4>Thongpoon Rice</h4>
                <p>ใบเสร็จรับเงิน / Receipt</p>
            </div>

            <div class="receipt-body">
                <div class="status-badge">✅ ชำระเงินเรียบร้อยแล้ว!</div>

              <!-- แถวที่ 1 -->
<div class="order-info mb-2">
    <div class="info-item">
        <div class="info-label">เลขออเดอร์</div>
        <div class="info-value">#<?= $order['order_id'] ?></div>
    </div>
    <div class="info-item">
        <div class="info-label">โต๊ะ</div>
        <div class="info-value"><?= htmlspecialchars($order['table_name']) ?></div>
    </div>
    <div class="info-item">
        <div class="info-label">วันที่</div>
        <div class="info-value"><?= date('d/m/Y', strtotime($order['order_date'])) ?></div>
    </div>
    <div class="info-item">
        <div class="info-label">เวลา</div>
        <div class="info-value"><?= date('H:i', strtotime($order['order_date'])) ?></div>
    </div>
</div>

<!-- แถวที่ 2 -->
<div class="order-info">
    <div class="info-item">
        <div class="info-label">ประเภท</div>
        <div class="info-value">
            <?= $order['order_type'] === 'takeaway' ? '🛍️ กลับบ้าน' : '🪑 ทานที่ร้าน' ?>
        </div>
    </div>
    <div class="info-item">
        <div class="info-label">ชำระโดย</div>
        <div class="info-value">
            <?= $order['pay_method'] === 'qr' ? '📱 QR Code' : '💵 เงินสด' ?>
        </div>
    </div>
</div>

                <hr class="dashed-line">

                <div class="table-header">
                    <span style="flex:1">รายการ</span>
                    <span style="width:40px; text-align:center">จำนวน</span>
                    <span style="width:70px; text-align:right">ราคา</span>
                </div>

                <?php foreach ($items as $item): ?>
                <div class="item-row">
                    <div class="item-name">
                        <?= htmlspecialchars($item['menu_name']) ?>
                        <?php if (!empty($item['remark'])): ?>
                        <div class="item-remark">
                            📝 <?= htmlspecialchars($item['remark']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="item-qty">x<?= $item['quantity'] ?></div>
                    <div class="item-price">
                        <?= number_format($item['price'] * $item['quantity'], 0) ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="total-section">
                    <div class="total-final">
                        <span>รวมทั้งหมด</span>
                        <span>
                            <?= number_format($order['total_price'], 0) ?> บาท
                        </span>
                    </div>
                </div>
            </div>

            <div class="receipt-footer">
                <div class="thank-you">ขอบคุณที่ใช้บริการ 🙏</div>
                <p>Thongpoon Rice </p>
            </div>

            <a href="index.php?table=<?= $table_id ?>" class="btn-order-more">
                <i class="fas fa-plus-circle"></i> สั่งอาหารเพิ่ม
            </a>
        </div>
    </div>
</div>

<script>
const orderId = <?= $order_id ?>;

// เช็คสถานะทุก 5 วินาที
function checkStatus() {
    fetch('check_status.php?order_id=' + orderId)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'paid') {
            // พนักงานยืนยันแล้ว → แสดงใบเสร็จ
            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('receipt-screen').style.display = 'block';
        }
        // ถ้ายังเป็น pending → รอต่อไป
    });
}

// เริ่มเช็คทันทีและทุก 5 วินาที
checkStatus();
setInterval(checkStatus, 5000);
</script>

</body>
</html>