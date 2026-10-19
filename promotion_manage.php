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

// ── บันทึกข้อมูล ──
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // อัปเดตโปรโมชัน
    if ($action === 'update_promo') {
    $pro_id      = (int)$_POST['pro_id'];
    $pro_name    = trim($_POST['pro_name']);
    $description = trim($_POST['description']);
    $kpi_target  = (int)$_POST['kpi_target'];
    $is_active   = isset($_POST['is_active']) ? 1 : 0;
    $start_date  = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date    = !empty($_POST['end_date'])   ? $_POST['end_date']   : null;

    $stmt = $pdo->prepare("
        UPDATE promotion 
        SET pro_name=?, description=?, kpi_target=?, is_active=?,
            start_date=?, end_date=?
        WHERE pro_id=?
    ");
    $stmt->execute([$pro_name, $description, $kpi_target, $is_active,
                    $start_date, $end_date, $pro_id]);
    $success_msg = 'บันทึกข้อมูลโปรโมชันเรียบร้อยแล้ว ✅';
}

    // เพิ่มเมนูเข้าโปร
    if ($action === 'add_menu') {
        $pro_id  = (int)$_POST['pro_id'];
        $menu_id = trim($_POST['menu_id']);

        // เช็คว่ามีอยู่แล้วมั้ย
        $check = $pdo->prepare("SELECT id FROM promotion_menu WHERE pro_id=? AND menu_id=?");
        $check->execute([$pro_id, $menu_id]);
        if ($check->fetch()) {
            $error_msg = 'เมนูนี้อยู่ในโปรโมชันแล้ว';
        } else {
            $pdo->prepare("INSERT INTO promotion_menu (pro_id, menu_id) VALUES (?,?)")
                ->execute([$pro_id, $menu_id]);
            $success_msg = 'เพิ่มเมนูเข้าโปรโมชันแล้ว ✅';
        }
    }

    // ลบเมนูออกจากโปร
    if ($action === 'remove_menu') {
        $id = (int)$_POST['pm_id'];
        $pdo->prepare("DELETE FROM promotion_menu WHERE id=?")->execute([$id]);
        $success_msg = 'ลบเมนูออกจากโปรโมชันแล้ว ✅';
    }

    // เปิด/ปิดโปรโมชัน
    if ($action === 'toggle') {
        $pro_id    = (int)$_POST['pro_id'];
        $is_active = (int)$_POST['is_active'];
        $pdo->prepare("UPDATE promotion SET is_active=? WHERE pro_id=?")
            ->execute([$is_active, $pro_id]);
        $success_msg = $is_active ? 'เปิดโปรโมชันแล้ว ✅' : 'ปิดโปรโมชันแล้ว ✅';
    }
}

// ── ดึงข้อมูล ──
$promotions = $pdo->query("SELECT * FROM promotion ORDER BY pro_id")->fetchAll();
$all_menus  = $pdo->query("SELECT menu_id, menu_name, sale_price FROM menu WHERE is_available=1 ORDER BY sale_price")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการโปรโมชัน - Thongpoon Rice</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; font-family: 'Kanit', sans-serif; }
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
        .card-box {
            background: white; border-radius: 14px;
            padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            margin-bottom: 24px;
        }
        .section-title {
            font-weight: 500; font-size: 16px; color: #333;
            border-left: 4px solid #e74c3c;
            padding-left: 10px; margin-bottom: 16px;
        }
        .menu-tag {
            display: inline-flex; align-items: center; gap: 6px;
            background: #fff5f5; border: 1.5px solid #e74c3c;
            color: #c0392b; border-radius: 20px;
            padding: 4px 12px; font-size: 13px; margin: 4px;
        }
        .menu-tag button {
            background: none; border: none; color: #e74c3c;
            cursor: pointer; font-size: 14px; padding: 0; line-height: 1;
        }
        .toggle-switch {
            position: relative; display: inline-block;
            width: 52px; height: 28px;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #ccc; border-radius: 28px;
            transition: .3s;
        }
        .toggle-slider:before {
            position: absolute; content: "";
            height: 20px; width: 20px; left: 4px; bottom: 4px;
            background: white; border-radius: 50%; transition: .3s;
        }
        input:checked + .toggle-slider { background: #e74c3c; }
        input:checked + .toggle-slider:before { transform: translateX(24px); }
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
    <a href="promotion_report.php"><i class="fas fa-gift"></i> รายงานโปรโมชัน</a>
    <a href="promotion_manage.php" class="active">
        <i class="fas fa-cog"></i> จัดการโปรโมชัน
    </a>
    <a href="logout.php" style="position:absolute; bottom:20px; width:100%;">
        <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
    </a>
</div>

<div class="main-content">
    <div class="mb-4">
        <h4 class="fw-normal mb-0">⚙️ จัดการโปรโมชัน</h4>
        <small class="text-muted">แก้ไขชื่อ เป้าหมาย เมนู และสถานะโปรโมชันได้เลย</small>
    </div>

    <?php if($success_msg): ?>
    <div class="alert alert-success rounded-3 mb-4">✅ <?= $success_msg ?></div>
    <?php endif; ?>
    <?php if($error_msg): ?>
    <div class="alert alert-danger rounded-3 mb-4">❌ <?= $error_msg ?></div>
    <?php endif; ?>

    <?php foreach($promotions as $promo): ?>
    <?php
        // ดึงเมนูในโปรนี้
        $promo_menus = $pdo->prepare("
            SELECT pm.id, m.menu_id, m.menu_name, m.sale_price
            FROM promotion_menu pm
            JOIN menu m ON pm.menu_id = m.menu_id
            WHERE pm.pro_id = ?
        ");
        $promo_menus->execute([$promo['pro_id']]);
        $promo_menu_list = $promo_menus->fetchAll();
        $promo_menu_ids  = array_column($promo_menu_list, 'menu_id');
    ?>
    <div class="card-box">
        <!-- หัวการ์ด -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="section-title mb-0">🎁 โปรโมชัน #<?= $promo['pro_id'] ?></div>

            <!-- Toggle เปิด/ปิด -->
            <form method="POST" class="d-flex align-items-center gap-2">
                <input type="hidden" name="action"    value="toggle">
                <input type="hidden" name="pro_id"    value="<?= $promo['pro_id'] ?>">
                <input type="hidden" name="is_active" value="<?= $promo['is_active'] ? 0 : 1 ?>">
                <small class="text-muted"><?= $promo['is_active'] ? 'เปิดอยู่' : 'ปิดอยู่' ?></small>
                <label class="toggle-switch">
                    <input type="checkbox" <?= $promo['is_active'] ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    <span class="toggle-slider"></span>
                </label>
            </form>
        </div>

        <!-- ฟอร์มแก้ไขโปรโมชัน -->
        <form method="POST">
            <input type="hidden" name="action" value="update_promo">
            <input type="hidden" name="pro_id" value="<?= $promo['pro_id'] ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-normal">ชื่อโปรโมชัน</label>
                    <input type="text" name="pro_name" class="form-control"
                           value="<?= htmlspecialchars($promo['pro_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-normal">
                        🎯 เป้าหมาย KPI
                        <small class="text-muted fw-normal">(จำนวนครั้งที่ถือว่าสำเร็จ)</small>
                    </label>
                    <input type="number" name="kpi_target" class="form-control"
                           value="<?= $promo['kpi_target'] ?>" min="1" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-normal">รายละเอียดโปรโมชัน</label>
                    <input type="text" name="description" class="form-control"
                           value="<?= htmlspecialchars($promo['description']) ?>">
                    <div class="col-md-6">
    <label class="form-label fw-normal">📅 วันที่เริ่มโปรโมชัน</label>
    <input type="date" name="start_date" class="form-control"
           value="<?= $promo['start_date'] ?? '' ?>">
</div>
<div class="col-md-6">
    <label class="form-label fw-normal">📅 วันที่สิ้นสุด</label>
    <input type="date" name="end_date" class="form-control"
           value="<?= $promo['end_date'] ?? '' ?>">
</div>
                </div>
            </div>

            <button type="submit" class="btn btn-danger rounded-pill px-4">
                <i class="fas fa-save"></i> บันทึก
            </button>
        </form>

        <hr class="my-4">

        <!-- เมนูที่เข้าร่วมโปร -->
        <div class="fw-normal mb-2">🍽️ เมนูที่เข้าร่วมโปรโมชัน</div>
        <div class="mb-3">
            <?php if(empty($promo_menu_list)): ?>
            <span class="text-muted">ยังไม่มีเมนู</span>
            <?php else: ?>
            <?php foreach($promo_menu_list as $pm): ?>
            <span class="menu-tag">
                <?= htmlspecialchars($pm['menu_name']) ?>
                (<?= $pm['sale_price'] ?> บาท)
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="remove_menu">
                    <input type="hidden" name="pm_id"  value="<?= $pm['id'] ?>">
                    <button type="submit" title="ลบออก"
                            onclick="return confirm('ลบเมนูนี้ออกจากโปรโมชันมั้ย?')">
                        ✕
                    </button>
                </form>
            </span>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- เพิ่มเมนูเข้าโปร -->
        <form method="POST" class="d-flex gap-2 align-items-end">
            <input type="hidden" name="action" value="add_menu">
            <input type="hidden" name="pro_id" value="<?= $promo['pro_id'] ?>">
            <div class="flex-fill">
                <label class="form-label fw-normal mb-1">เพิ่มเมนูเข้าโปรโมชัน</label>
                <select name="menu_id" class="form-select">
                    <?php foreach($all_menus as $m): ?>
                    <?php if(!in_array($m['menu_id'], $promo_menu_ids)): ?>
                    <option value="<?= $m['menu_id'] ?>">
                        <?= htmlspecialchars($m['menu_name']) ?> (<?= $m['sale_price'] ?> บาท)
                    </option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-outline-danger rounded-pill px-4">
                <i class="fas fa-plus"></i> เพิ่ม
            </button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

</body>
</html>