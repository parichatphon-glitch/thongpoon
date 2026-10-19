<?php
session_start();
require '../connections/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// เฉพาะเจ้าของร้านเท่านั้น
if ($_SESSION['user']['role'] !== 'owner') {
    header("Location: dashboard.php");
    exit;
}

$user = $_SESSION['user'];

// รับค่ากรองจาก URL
$filter = $_GET['filter'] ?? 'day';
$date   = $_GET['date']   ?? date('Y-m-d');
$month  = $_GET['month']  ?? date('Y-m');
$year   = $_GET['year']   ?? date('Y');

// ===== ยอดขายตามช่วงเวลา =====
if ($filter === 'day') {
    $sales = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_price), 0) as total_sales
        FROM orders
        WHERE payment_status = 'paid'
        AND DATE(order_date) = ?
    ");
    $sales->execute([$date]);

    // ยอดขายรายชั่วโมง
    $chart = $pdo->prepare("
        SELECT 
            HOUR(order_date) as label,
            COALESCE(SUM(total_price), 0) as amount
        FROM orders
        WHERE payment_status = 'paid'
        AND DATE(order_date) = ?
        GROUP BY HOUR(order_date)
        ORDER BY label
    ");
    $chart->execute([$date]);
    $chart_label = 'ยอดขายรายชั่วโมง';

} elseif ($filter === 'month') {
    $sales = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_price), 0) as total_sales
        FROM orders
        WHERE payment_status = 'paid'
        AND DATE_FORMAT(order_date, '%Y-%m') = ?
    ");
    $sales->execute([$month]);

    // ยอดขายรายวันในเดือน
    $chart = $pdo->prepare("
        SELECT 
            DAY(order_date) as label,
            COALESCE(SUM(total_price), 0) as amount
        FROM orders
        WHERE payment_status = 'paid'
        AND DATE_FORMAT(order_date, '%Y-%m') = ?
        GROUP BY DAY(order_date)
        ORDER BY label
    ");
    $chart->execute([$month]);
    $chart_label = 'ยอดขายรายวัน';

} else {
    $sales = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_price), 0) as total_sales
        FROM orders
        WHERE payment_status = 'paid'
        AND YEAR(order_date) = ?
    ");
    $sales->execute([$year]);

    // ยอดขายรายเดือนในปี
    $chart = $pdo->prepare("
        SELECT 
            MONTH(order_date) as label,
            COALESCE(SUM(total_price), 0) as amount
        FROM orders
        WHERE payment_status = 'paid'
        AND YEAR(order_date) = ?
        GROUP BY MONTH(order_date)
        ORDER BY label
    ");
    $chart->execute([$year]);
    $chart_label = 'ยอดขายรายเดือน';
}

$summary   = $sales->fetch();
$chart_data = $chart->fetchAll();

// ===== เมนูยอดนิยม (Top 5) =====
$top_menus = $pdo->query("
    SELECT 
        m.menu_name,
        SUM(od.quantity) as total_qty,
        SUM(od.quantity * od.price) as total_income
    FROM order_detail od
    JOIN menu m ON od.menu_id = m.menu_id
    JOIN orders o ON od.order_id = o.order_id
    WHERE o.payment_status = 'paid'
    GROUP BY od.menu_id
    ORDER BY total_qty DESC
    LIMIT 5
")->fetchAll();

// เตรียมข้อมูล Chart
$labels  = array_column($chart_data, 'label');
$amounts = array_column($chart_data, 'amount');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานยอดขาย - ร้านข้าวทองพูน</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* การ์ดสถิติ */
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
        .stat-value {
            font-size: 26px;
            font-weight: 500;
            color: #333;
            line-height: 1;
        }
        .stat-label { color: #888; font-size: 13px; margin-top: 4px; }

        /* กล่องกราฟและตาราง */
        .box {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            margin-bottom: 20px;
        }
        .box-title {
            font-weight: 500;
            font-size: 16px;
            color: #333;
            border-left: 4px solid #e74c3c;
            padding-left: 10px;
            margin-bottom: 16px;
        }

        /* แถบกรอง */
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 7px 20px;
            border-radius: 20px;
            border: 2px solid #ddd;
            background: white;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            color: #555;
            transition: all 0.2s;
        }
        .filter-tab:hover, .filter-tab.active {
            border-color: #e74c3c;
            color: #e74c3c;
            background: #fff5f5;
        }

        /* ตารางเมนูยอดนิยม */
        .top-table { width: 100%; border-collapse: collapse; }
        .top-table th {
            background: #fff5f5;
            color: #e74c3c;
            padding: 10px 12px;
            font-size: 14px;
            border-bottom: 2px solid #f0f0f0;
        }
        .top-table td {
            padding: 10px 12px;
            font-size: 14px;
            border-bottom: 1px solid #f8f8f8;
        }
        .rank-badge {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 13px;
            color: white;
        }
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
            <?= htmlspecialchars($user['firstname']) ?> (เจ้าของร้าน)
        </small>
    </div>
    <a href="dashboard.php"><i class="fas fa-home"></i> หน้าหลัก</a>
    <a href="orders.php"><i class="fas fa-receipt"></i> จัดการออเดอร์</a>
    <a href="menu.php"><i class="fas fa-utensils"></i> จัดการเมนู</a>
    <a href="report.php" class="active">
        <i class="fas fa-chart-bar"></i> รายงานยอดขาย
        <a href="promotion_report.php">
    <i class="fas fa-gift"></i> รายงานโปรโมชัน
    <a href="promotion_manage.php">
    <i class="fas fa-cog"></i> จัดการโปรโมชัน</a>

</a>
    </a>
    <a href="logout.php" style="position:absolute; bottom:20px; width:100%;">
        <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="mb-4">
        <h4 class="fw-normal mb-0">รายงานยอดขาย</h4>
        <small class="text-muted">สรุปผลการขายของร้าน Thongpoon Rice</small>
    </div>

    <!-- แถบเลือกช่วงเวลา -->
    <div class="filter-tabs">
        <a href="?filter=day&date=<?= $date ?>"
           class="filter-tab <?= $filter==='day' ? 'active' : '' ?>">
            📅 รายวัน
        </a>
        <a href="?filter=month&month=<?= $month ?>"
           class="filter-tab <?= $filter==='month' ? 'active' : '' ?>">
            📆 รายเดือน
        </a>
        <a href="?filter=year&year=<?= $year ?>"
           class="filter-tab <?= $filter==='year' ? 'active' : '' ?>">
            🗓️ รายปี
        </a>
    </div>

    <!-- ตัวเลือกวัน/เดือน/ปี -->
    <div class="box mb-4">
        <?php if ($filter === 'day'): ?>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="filter" value="day">
            <label class="fw-normal">เลือกวันที่:</label>
            <input type="date" name="date" value="<?= $date ?>"
                   class="form-control" style="width:180px;">
            <button class="btn btn-danger rounded-pill px-4">ดูรายงาน</button>
        </form>

        <?php elseif ($filter === 'month'): ?>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="filter" value="month">
            <label class="fw-normal">เลือกเดือน:</label>
            <input type="month" name="month" value="<?= $month ?>"
                   class="form-control" style="width:180px;">
            <button class="btn btn-danger rounded-pill px-4">ดูรายงาน</button>
        </form>

        <?php else: ?>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="filter" value="year">
            <label class="fw-normal">เลือกปี:</label>
            <select name="year" class="form-select" style="width:140px;">
                <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
                <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>>
                    <?= $y + 543 ?>
                </option>
                <?php endfor; ?>
            </select>
            <button class="btn btn-danger rounded-pill px-4">ดูรายงาน</button>
        </form>
        <?php endif; ?>
    </div>

    <!-- การ์ดสรุป -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:#d4edda;">🧾</div>
                <div>
                    <div class="stat-value">
                        <?= number_format($summary['total_orders']) ?>
                    </div>
                    <div class="stat-label">ออเดอร์ที่สำเร็จ</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f8d7da;">💰</div>
                <div>
                    <div class="stat-value">
                        <?= number_format($summary['total_sales'], 0) ?>
                    </div>
                    <div class="stat-label">ยอดขายรวม (บาท)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- กราฟยอดขาย -->
    <div class="box">
        <div class="box-title">📊 <?= $chart_label ?></div>
        <?php if (empty($chart_data)): ?>
        <div class="text-center py-4 text-muted">
            <div style="font-size:40px;">📭</div>
            <p>ไม่มีข้อมูลในช่วงเวลานี้</p>
        </div>
        <?php else: ?>
        <canvas id="salesChart" height="100"></canvas>
        <?php endif; ?>
    </div>

    <!-- เมนูยอดนิยม -->
    <div class="box">
        <div class="box-title">🏆 เมนูยอดนิยม Top 5</div>
        <?php if (empty($top_menus)): ?>
        <div class="text-center py-4 text-muted">ยังไม่มีข้อมูล</div>
        <?php else: ?>
        <table class="top-table">
            <thead>
                <tr>
                    <th>อันดับ</th>
                    <th>ชื่อเมนู</th>
                    <th class="text-center">จำนวนที่ขาย</th>
                    <th class="text-end">รายได้รวม</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rank_colors = ['#f39c12','#95a5a6','#cd7f32','#e74c3c','#3498db'];
                $rank_emoji  = ['🥇','🥈','🥉','4️⃣','5️⃣'];
                foreach ($top_menus as $i => $menu):
                ?>
                <tr>
                    <td>
                        <span class="rank-badge"
                              style="background:<?= $rank_colors[$i] ?>">
                            <?= $i + 1 ?>
                        </span>
                    </td>
                    <td>
                        <?= $rank_emoji[$i] ?>
                        <?= htmlspecialchars($menu['menu_name']) ?>
                    </td>
                    <td class="text-center fw-normal">
                        <?= number_format($menu['total_qty']) ?> จาน
                    </td>
                    <td class="text-end text-danger fw-normal">
                        <?= number_format($menu['total_income'], 0) ?> บาท
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
<?php if (!empty($chart_data)): ?>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels : <?= json_encode($labels) ?>,
        datasets: [{
            label    : 'ยอดขาย (บาท)',
            data     : <?= json_encode($amounts) ?>,
            backgroundColor: 'rgba(231, 76, 60, 0.7)',
            borderColor    : 'rgba(192, 57, 43, 1)',
            borderWidth    : 2,
            borderRadius   : 8,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: val => val.toLocaleString() + ' ฿'
                }
            }
        }
    }
});
<?php endif; ?>
</script>

</body>
</html>
```
