<?php
session_start();
require '../connections/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// ดึงออเดอร์ทั้งหมดวันนี้ พร้อมชื่อโต๊ะ
$orders = $pdo->query("
    SELECT o.*, t.table_name
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.table_id
    WHERE DATE(o.order_date) = CURDATE()
    ORDER BY o.order_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการออเดอร์ - ร้านข้าวทองพูน</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; font-family: 'Kanit', sans-serif; }
        .sidebar {
            background: linear-gradient(180deg, #c0392b, #922b21);
            min-height: 100vh;
            width: 220px;
            position: fixed;
            top: 0; left: 0;
            padding: 20px 0;
            z-index: 100;
        }
        .sidebar .brand {
            text-align: center;
            color: white;
            padding: 10px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 10px;
        }
        .sidebar .brand .icon { font-size: 36px; }
        .sidebar .brand h6 { margin: 6px 0 2px; font-weight: 500; }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.85);
            padding: 12px 20px;
            text-decoration: none;
            font-size: 15px;
            transition: all 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        .sidebar a i { width: 20px; text-align: center; }
        .main-content { margin-left: 220px; padding: 24px; }

        /* แถบกรอง */
        .filter-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-btn {
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 14px;
            border: 2px solid #ddd;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn.active, .filter-btn:hover {
            border-color: #e74c3c;
            color: #e74c3c;
            background: #fff5f5;
        }

        /* การ์ดออเดอร์ */
        .order-card {
            background: white;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border-left: 5px solid #ddd;
            transition: all 0.2s;
        }
        .order-card.pending  { border-left-color: #f39c12; }
        .order-card.paid     { border-left-color: #27ae60; }
        .order-card.cancelled{ border-left-color: #e74c3c; }

        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        .badge-pending   { background: #fff3cd; color: #856404; }
        .badge-paid      { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }

        /* รายละเอียด */
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 14px;
            border-bottom: 1px dashed #f0f0f0;
        }
        .detail-row:last-child { border-bottom: none; }

        /* ปุ่มดำเนินการ */
        .btn-action {
            border-radius: 8px;
            padding: 7px 14px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .btn-confirm-pay {
            background: #27ae60;
            color: white;
        }
        .btn-confirm-pay:hover { background: #219a52; }
        .btn-cancel {
            background: #e74c3c;
            color: white;
        }
        .btn-cancel:hover { background: #c0392b; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">
        <img src="/thongpoonrice/assets/images/logo.png" 
         style="height:75px; width:75px; border-radius:50%; object-fit:cover; margin-right:8px;">
        <h6>Thongpoon Rice</h6>
        <small style="color:rgba(255,255,255,0.6);">
            <?= htmlspecialchars($user['firstname']) ?>
            (<?= $user['role'] === 'owner' ? 'เจ้าของร้าน' : 'พนักงาน' ?>)
        </small>
    </div>
    <a href="dashboard.php"><i class="fas fa-home"></i> หน้าหลัก</a>
    <a href="orders.php" class="active">
        <i class="fas fa-receipt"></i> จัดการออเดอร์
    </a>
    <a href="menu.php"><i class="fas fa-utensils"></i> จัดการเมนู</a>
    <?php if ($user['role'] === 'owner'): ?>
<a href="report.php">
    <i class="fas fa-chart-bar"></i> รายงานยอดขาย
</a>
<a href="promotion_report.php">
    <i class="fas fa-gift"></i> รายงานโปรโมชัน
<a href="promotion_manage.php">
    <i class="fas fa-cog"></i> จัดการโปรโมชัน</a>
</a>
<?php endif; ?>
    <a href="logout.php" style="position:absolute; bottom:20px; width:100%;">
        <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-normal mb-0">จัดการออเดอร์</h4>
            <small class="text-muted">ออเดอร์ภายในวันนี้</small>
        </div>
        <!-- ปุ่ม refresh -->
        <button class="btn btn-outline-danger rounded-pill"
                onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> รีเฟรช
        </button>
    </div>

    <!-- แถบกรองสถานะ -->
    <div class="filter-bar">
        <button class="filter-btn active" onclick="filterOrders('all', this)">
            ทั้งหมด (<?= count($orders) ?>)
        </button>
        <button class="filter-btn" onclick="filterOrders('pending', this)">
            ⏳ รอดำเนินการ
        </button>
        <button class="filter-btn" onclick="filterOrders('paid', this)">
            ✅ จ่ายแล้ว
        </button>
        <button class="filter-btn" onclick="filterOrders('cancelled', this)">
            ❌ ยกเลิก
        </button>
    </div>

    <!-- รายการออเดอร์ -->
    <?php if (empty($orders)): ?>
    <div class="text-center py-5 text-muted">
        <div style="font-size:50px;">📋</div>
        <p>ยังไม่มีออเดอร์วันนี้</p>
    </div>
    <?php else: ?>

    <?php foreach ($orders as $order): ?>
    <?php
        // ดึงรายละเอียดออเดอร์
        $details = $pdo->prepare("
            SELECT od.*, m.menu_name
            FROM order_detail od
            JOIN menu m ON od.menu_id = m.menu_id
            WHERE od.order_id = ?
        ");
        $details->execute([$order['order_id']]);
        $items = $details->fetchAll();
    ?>
    <div class="order-card <?= $order['payment_status'] ?>"
         data-status="<?= $order['payment_status'] ?>">

        <!-- หัวออเดอร์ -->
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <span class="fw-normal fs-5">
                    #<?= $order['order_id'] ?>
                </span>
                <span class="text-muted ms-2">
                    <?= htmlspecialchars($order['table_name']) ?>
                </span><br>
                <small class="text-muted">
                    <i class="fas fa-clock"></i>
                    <?= date('H:i น.', strtotime($order['order_date'])) ?>
                </small>
            </div>
            <span class="badge-status badge-<?= $order['payment_status'] ?>">
                <?php
                    $status_text = [
                        'pending'   => '⏳ รอดำเนินการ',
                        'paid'      => '✅ จ่ายแล้ว',
                        'cancelled' => '❌ ยกเลิก'
                    ];
                    echo $status_text[$order['payment_status']];
                ?>
            </span>
        </div>

        <!-- รายการอาหาร -->
        <div class="mb-3">
            <?php foreach ($items as $item): ?>
            <div class="detail-row">
                <span>
                    <?= htmlspecialchars($item['menu_name']) ?>
                    <span class="text-muted">x<?= $item['quantity'] ?></span>
                    <?php if (!empty($item['remark'])): ?>
                    <br><small class="text-muted">
                        📝 <?= htmlspecialchars($item['remark']) ?>
                    </small>
                    <?php endif; ?>
                </span>
                <span class="fw-normal">
                    <?= number_format($item['price'] * $item['quantity'], 0) ?> บาท
                </span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ราคารวม -->
        <div class="d-flex justify-content-between align-items-center
                    pt-2 border-top">
            <span class="fw-normal">รวมทั้งหมด</span>
            <span class="fw-normal fs-5 text-danger">
                <?= number_format($order['total_price'], 0) ?> บาท
            </span>
        </div>

        <!-- ปุ่มดำเนินการ (แสดงเฉพาะออเดอร์ที่ยังรอ) -->
        <?php if ($order['payment_status'] === 'pending'): ?>
        <div class="d-flex gap-2 mt-3">
            <button class="btn-action btn-confirm-pay flex-fill"
                    onclick="updateStatus(<?= $order['order_id'] ?>, 'paid')">
                <i class="fas fa-check"></i> ยืนยันรับเงินแล้ว → แจ้งครัว
            </button>
            <button class="btn-action btn-cancel"
                    onclick="updateStatus(<?= $order['order_id'] ?>, 'cancelled')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// กรองออเดอร์ตามสถานะ
function filterOrders(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(b =>
        b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.order-card').forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// อัปเดตสถานะออเดอร์
function updateStatus(orderId, status) {
    const msg = status === 'paid'
        ? 'ยืนยันว่าลูกค้าชำระเงินแล้ว และแจ้งครัวให้ทำอาหาร?'
        : 'ยืนยันการยกเลิกออเดอร์นี้?';

    if (!confirm(msg)) return;

    fetch('update_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, status: status })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    });
}
</script>

</body>
</html>