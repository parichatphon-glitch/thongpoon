<?php
session_start();
require '../connections/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php"); exit;
}

$user = $_SESSION['user'];

if ($user['role'] !== 'owner') {
    header("Location: dashboard.php"); exit;
}

$period = $_GET['period'] ?? 'all';

$where = match($period) {
    'today' => "AND DATE(o.order_date) = CURDATE()",
    'month' => "AND MONTH(o.order_date) = MONTH(CURDATE()) AND YEAR(o.order_date) = YEAR(CURDATE())",
    'year'  => "AND YEAR(o.order_date) = YEAR(CURDATE())",
    default => ""
};

$promo_report = $pdo->query("
    SELECT 
        p.pro_id, p.pro_name, p.description, p.kpi_target,
        p.start_date, p.end_date, p.is_active,
        COUNT(o.order_id) AS use_count,
        COALESCE(SUM(o.total_price), 0) AS total_revenue
    FROM promotion p
    LEFT JOIN orders o ON o.pro_id = p.pro_id
        AND o.payment_status = 'paid'
        AND (p.start_date IS NULL OR DATE(o.order_date) >= p.start_date)
        AND (p.end_date   IS NULL OR DATE(o.order_date) <= p.end_date)
    WHERE p.is_active = 1
    GROUP BY p.pro_id
")->fetchAll();

$basket = $pdo->query("
    SELECT 
        a.menu_id AS menu_a, b.menu_id AS menu_b,
        ma.menu_name AS name_a, mb.menu_name AS name_b,
        COUNT(*) AS pair_count
    FROM order_detail a
    JOIN order_detail b ON a.order_id = b.order_id AND a.menu_id < b.menu_id
    JOIN menu ma ON a.menu_id = ma.menu_id
    JOIN menu mb ON b.menu_id = mb.menu_id
    WHERE a.menu_id NOT IN (SELECT free_menu_id FROM promotion WHERE free_menu_id IS NOT NULL)
      AND b.menu_id NOT IN (SELECT free_menu_id FROM promotion WHERE free_menu_id IS NOT NULL)
    GROUP BY a.menu_id, b.menu_id
    ORDER BY pair_count DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานโปรโมชัน - Thongpoon Rice</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; font-family: 'Kanit', sans-serif; font-weight: 400; }
        .sidebar {
            background: linear-gradient(180deg, #c0392b, #922b21);
            min-height: 100vh; width: 220px;
            position: fixed; top: 0; left: 0; padding: 20px 0; z-index: 100;
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
        .card-stat {
            background: white; border-radius: 12px;
            padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .kpi-bar { height: 10px; border-radius: 5px; background: #eee; overflow: hidden; }
        .kpi-fill { height: 100%; border-radius: 5px; transition: width .5s; }
        .badge-success { background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 20px; font-size: 12px; }
        .badge-danger  { background: #f8d7da; color: #721c24; padding: 4px 10px; border-radius: 20px; font-size: 12px; }
        .badge-warning { background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 20px; font-size: 12px; }
        .pair-card {
            background: white; border-radius: 10px;
            padding: 14px; margin-bottom: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            display: flex; justify-content: space-between; align-items: center;
        }
        .section-title {
            font-weight: 500; font-size: 18px; color: #333;
            border-left: 4px solid #e74c3c;
            padding-left: 10px; margin-bottom: 16px;
        }
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
            <?= htmlspecialchars($user['firstname']) ?> (เจ้าของร้าน)
        </small>
    </div>
    <a href="dashboard.php"><i class="fas fa-home"></i> หน้าหลัก</a>
    <a href="orders.php"><i class="fas fa-receipt"></i> จัดการออเดอร์</a>
    <a href="menu.php"><i class="fas fa-utensils"></i> จัดการเมนู</a>
    <a href="report.php"><i class="fas fa-chart-bar"></i> รายงานยอดขาย</a>
    <a href="promotion_report.php" class="active">
        <i class="fas fa-gift"></i> รายงานโปรโมชัน
    </a>
    <a href="promotion_manage.php">
        <i class="fas fa-cog"></i> จัดการโปรโมชัน
    </a>
    <a href="logout.php" style="position:absolute; bottom:20px; width:100%;">
        <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
    </a>
</div>

<div class="main-content">
    <div class="mb-4">
        <h4 class="fw-normal mb-0">🎁 รายงานโปรโมชัน</h4>
        <small class="text-muted">วิเคราะห์ผลการใช้งานโปรโมชันและพฤติกรรมลูกค้า</small>
    </div>

    

    <!-- สถิติโปรโมชัน -->
    <div class="section-title">📈 สถิติการใช้โปรโมชัน</div>
    <div class="row mb-4 justify-content-center">
        <?php foreach($promo_report as $promo):
            $pct    = $promo['kpi_target'] > 0
                    ? min(100, ($promo['use_count'] / $promo['kpi_target']) * 100)
                    : 0;
            $status = $promo['use_count'] >= $promo['kpi_target'] ? 'success'
                    : ($promo['use_count'] >= ($promo['kpi_target'] * 0.5) ? 'warning' : 'danger');
            $label  = $status === 'success' ? '✅ ประสบความสำเร็จ'
                    : ($status === 'warning' ? '⚠️ ปานกลาง' : '🔴 ควรปรับปรุง');
            $color  = $status === 'success' ? '#28a745'
                    : ($status === 'warning' ? '#ffc107' : '#dc3545');

            $days = '-';
            if ($promo['start_date'] && $promo['end_date']) {
                $diff = round((strtotime($promo['end_date']) - strtotime($promo['start_date'])) / 86400) + 1;
                $days = $diff . ' วัน';
            }

            $date_range = '-';
            if ($promo['start_date'] && $promo['end_date']) {
                $s = date('d/m/Y', strtotime($promo['start_date']));
                $e = date('d/m/Y', strtotime($promo['end_date']));
                $date_range = "$s — $e";
            }
        ?>
        <div class="col-md-8 mb-4">
            <div class="card-stat">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="fw-normal fs-6"><?= htmlspecialchars($promo['pro_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($promo['description']) ?></small>
                        <div class="mt-1">
                            <small class="text-muted">📅 <?= $date_range ?> (<?= $days ?>)</small>
                        </div>
                    </div>
                    <span class="badge-<?= $status ?>"><?= $label ?></span>
                </div>

                <div class="row text-center my-3">
                    <div class="col">
                        <div class="fs-2 fw-normal text-danger"><?= $promo['use_count'] ?></div>
                        <small class="text-muted">ครั้งที่ใช้</small>
                    </div>
                    <div class="col">
                        <div class="fs-2 fw-normal text-success">฿<?= number_format($promo['total_revenue']) ?></div>
                        <small class="text-muted">ยอดขายรวม</small>
                    </div>
                    <div class="col">
                        <div class="fs-2 fw-normal text-primary"><?= $promo['kpi_target'] ?></div>
                        <small class="text-muted">เป้าหมาย KPI</small>
                    </div>
                </div>

                <div class="d-flex justify-content-between mb-1">
                    <small class="text-muted">ความคืบหน้าสู่เป้าหมาย</small>
                    <small class="fw-normal"><?= round($pct) ?>%</small>
                </div>
                <div class="kpi-bar">
                    <div class="kpi-fill" style="width:<?= $pct ?>%; background:<?= $color ?>"></div>
                </div>

                <?php if($status === 'danger'): ?>
                <div class="mt-3 p-2 rounded" style="background:#fff5f5; border:1px solid #f5c6cb;">
                    <small class="text-danger fw-normal">
                        ⚠️ โปรโมชันนี้ถูกใช้งานน้อยกว่าเกณฑ์ ควรพิจารณาปรับเงื่อนไขหรือยกเลิก
                    </small>
                </div>
                <?php elseif($status === 'warning'): ?>
                <div class="mt-3 p-2 rounded" style="background:#fffbf0; border:1px solid #ffc107;">
                    <small class="fw-normal" style="color:#856404;">
                        📢 ใช้ไปแล้ว <?= round($pct) ?>% ของเป้าหมาย 
                    </small>
                </div>
                <?php else: ?>
                <div class="mt-3 p-2 rounded" style="background:#f0fff4; border:1px solid #c3e6cb;">
                    <small class="text-success fw-normal">
                        🎉 บรรลุเป้าหมายแล้ว! ควรขยายเวลาโปรโมชันนี้ต่อ
                    </small>
                </div>
                <?php endif; ?>

                <div class="mt-2 text-end">
                    <a href="promotion_manage.php" class="btn btn-sm btn-outline-danger rounded-pill">
                        <i class="fas fa-edit"></i> แก้ไขโปรโมชัน
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($promo_report)): ?>
        <div class="col-12 text-center text-muted py-4">
            <div style="font-size:40px;">🎁</div>
            <p>ยังไม่มีข้อมูลโปรโมชัน</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Market Basket -->
    <div class="section-title">🛒 เมนูที่ลูกค้าสั่งคู่กันบ่อย (Market Basket)</div>

    <?php if(empty($basket)): ?>
    <div class="text-center text-muted py-4">
        <div style="font-size:40px;">🛒</div>
        <p>ยังไม่มีข้อมูลเพียงพอ</p>
    </div>
    <?php else: ?>
    <?php foreach($basket as $i => $pair): ?>
    <div class="pair-card">
        <div class="d-flex align-items-center gap-3">
            <div class="fw-normal text-muted" style="font-size:20px;">#<?= $i+1 ?></div>
            <div>
                <span class="badge bg-danger me-1"><?= htmlspecialchars($pair['name_a']) ?></span>
                <span class="text-muted mx-1">+</span>
                <span class="badge bg-dark"><?= htmlspecialchars($pair['name_b']) ?></span>
            </div>
        </div>
        <div class="text-end">
            <div class="fw-normal text-danger fs-5"><?= number_format($pair['pair_count']) ?> ครั้ง</div>
            <small class="text-muted">สั่งคู่กัน</small>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="card-stat mt-4" style="border-left: 4px solid #e74c3c;">
        <div class="fw-normal mb-2">💡 สรุปผล</div>
        <p class="text-muted mb-0">
            เมนูที่ลูกค้าสั่งคู่กันบ่อยที่สุดคือ
            <strong class="text-danger"><?= htmlspecialchars($basket[0]['name_a']) ?></strong>
            กับ
            <strong class="text-danger"><?= htmlspecialchars($basket[0]['name_b']) ?></strong>
            (<?= number_format($basket[0]['pair_count']) ?> ครั้ง)
        </p>
    </div>
    <?php endif; ?>
</div>

</body>
</html>