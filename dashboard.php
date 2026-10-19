<?php
session_start();
require '../connections/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

$pending = $pdo->query("
    SELECT COUNT(*) as cnt FROM orders 
    WHERE payment_status = 'pending'
")->fetch();

$paid_today = $pdo->query("
    SELECT COUNT(*) as cnt FROM orders 
    WHERE payment_status = 'paid' 
    AND DATE(order_date) = CURDATE()
")->fetch();

$sales_today = $pdo->query("
    SELECT COALESCE(SUM(total_price), 0) as total FROM orders 
    WHERE payment_status = 'paid' 
    AND DATE(order_date) = CURDATE()
")->fetch();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Thongpoon Rice</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; font-family: 'Kanit', sans-serif; }
        .sidebar {
            background: linear-gradient(180deg, #c0392b, #922b21);
            min-height: 100vh; width: 220px;
            position: fixed; top: 0; left: 0;
            padding: 20px 0; z-index: 100;
        }
        .sidebar .brand {
            text-align: center; color: white;
            padding: 10px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 10px;
        }
        .sidebar .brand h6 { margin: 6px 0 2px; font-weight: 500; }
        .sidebar a {
            display: flex; align-items: center; gap: 10px;
            color: rgba(255,255,255,0.85);
            padding: 12px 20px; text-decoration: none;
            font-size: 15px; transition: all 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.15); color: white;
        }
        .sidebar a i { width: 20px; text-align: center; }
        .main-content { margin-left: 220px; padding: 24px; }
        .topbar {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 24px;
        }
        .stat-card {
            background: white; border-radius: 14px;
            padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            display: flex; align-items: center; gap: 16px;
        }
        .stat-icon {
            width: 56px; height: 56px; border-radius: 14px;
            display: flex; align-items: center;
            justify-content: center; font-size: 24px;
        }
        .stat-value { font-size: 26px; font-weight: 500; color: #333; line-height: 1; }
        .stat-label { color: #888; font-size: 13px; margin-top: 4px; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">
        <img src="/thongpoonrice/assets/images/logo.png"
             style="height:75px;width:75px;border-radius:50%;object-fit:cover;">
        <h6>Thongpoon Rice</h6>
        <small style="color:rgba(255,255,255,0.6);">
            <?= htmlspecialchars($user['firstname']) ?>
            (<?= $user['role'] === 'owner' ? 'เจ้าของร้าน' : 'พนักงาน' ?>)
        </small>
    </div>

    <a href="dashboard.php" class="active">
        <i class="fas fa-home"></i> หน้าหลัก
    </a>
    <a href="orders.php">
        <i class="fas fa-receipt"></i> จัดการออเดอร์
        <?php if ($pending['cnt'] > 0): ?>
        <span class="badge bg-warning text-dark ms-auto"><?= $pending['cnt'] ?></span>
        <?php endif; ?>
    </a>
    <a href="menu.php">
        <i class="fas fa-utensils"></i> จัดการเมนู
    </a>
    <?php if ($user['role'] === 'owner'): ?>
    <a href="report.php">
        <i class="fas fa-chart-bar"></i> รายงานยอดขาย
    </a>
    <a href="promotion_report.php">
        <i class="fas fa-gift"></i> รายงานโปรโมชัน
    </a>
    <a href="promotion_manage.php">
        <i class="fas fa-cog"></i> จัดการโปรโมชัน
    </a>
    <?php endif; ?>

    <a href="logout.php" style="position:absolute; bottom:20px; width:100%;">
        <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="topbar">
        <div>
            <h4 class="fw-normal mb-0">หน้าหลัก</h4>
            <small class="text-muted">วันที่ <span id="clock"></span></small>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3cd;">⏳</div>
                <div>
                    <div class="stat-value"><?= $pending['cnt'] ?></div>
                    <div class="stat-label">ออเดอร์รอดำเนินการ</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:#d4edda;">✅</div>
                <div>
                    <div class="stat-value"><?= $paid_today['cnt'] ?></div>
                    <div class="stat-label">ออเดอร์สำเร็จวันนี้</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f8d7da;">💰</div>
                <div>
                    <div class="stat-value"><?= number_format($sales_today['total'], 0) ?></div>
                    <div class="stat-label">ยอดขายวันนี้ (บาท)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateClock() {
    const now = new Date();
    const d   = String(now.getDate()).padStart(2,'0');
    const m   = String(now.getMonth()+1).padStart(2,'0');
    const y   = now.getFullYear();
    const h   = String(now.getHours()).padStart(2,'0');
    const min = String(now.getMinutes()).padStart(2,'0');
    const sec = String(now.getSeconds()).padStart(2,'0');
    document.getElementById('clock').textContent = `${d}/${m}/${y} ${h}:${min}:${sec}`;
}
updateClock();
setInterval(updateClock, 1000);
</script>

</body>
</html>