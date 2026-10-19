<?php
session_start();
require '../connections/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// ดึงเมนูทั้งหมด พร้อมชื่อหมวดหมู่
$menus = $pdo->query("
    SELECT m.*, c.cat_name 
    FROM menu m
    LEFT JOIN category c ON m.cat_id = c.cat_id
    ORDER BY m.cat_id, m.menu_id
")->fetchAll();

// ดึงหมวดหมู่ทั้งหมด (สำหรับ dropdown)
$categories = $pdo->query("SELECT * FROM category")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเมนู - ร้านข้าวทองพูน</title>
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

        /* การ์ดเมนู */
        .menu-card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            transition: transform 0.2s;
        }
        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .menu-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: #f5f5f5;
        }
        .menu-card-placeholder {
            width: 100%;
            height: 150px;
            background: linear-gradient(135deg, #f5f5f5, #e8e8e8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        .menu-card-body { padding: 12px; }
        .menu-card-name {
            font-weight: 500;
            font-size: 15px;
            color: #333;
        }
        .menu-card-price {
            color: #e74c3c;
            font-weight: 500;
            font-size: 16px;
        }
        .menu-card-cat {
            font-size: 12px;
            color: #888;
            background: #f5f5f5;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 8px;
        }
        /* badge หมด */
        .badge-unavail {
            background: #e74c3c;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
        }
        .badge-avail {
            background: #27ae60;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
        }
        /* ปุ่มในการ์ด */
        .card-actions {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }
        .card-actions button {
            flex: 1;
            padding: 5px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-edit-card { background: #fff3cd; color: #856404; }
        .btn-edit-card:hover { background: #ffc107; color: white; }
        .btn-del-card  { background: #f8d7da; color: #721c24; }
        .btn-del-card:hover  { background: #e74c3c; color: white; }

        /* Modal */
        .modal-header {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: white;
        }
        .form-label { font-weight: 500; font-size: 14px; }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #ddd;
        }
        .form-control:focus, .form-select:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231,76,60,0.1);
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
            <?= htmlspecialchars($user['firstname']) ?>
            (<?= $user['role'] === 'owner' ? 'เจ้าของร้าน' : 'พนักงาน' ?>)
        </small>
    </div>
    <a href="dashboard.php"><i class="fas fa-home"></i> หน้าหลัก</a>
    <a href="orders.php"><i class="fas fa-receipt"></i> จัดการออเดอร์</a>
    <a href="menu.php" class="active">
        <i class="fas fa-utensils"></i> จัดการเมนู
    </a>
    <?php if ($user['role'] === 'owner'): ?>
    <a href="report.php"><i class="fas fa-chart-bar"></i> รายงานยอดขาย</a>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-normal mb-0">จัดการเมนู</h4>
            <small class="text-muted">เมนูทั้งหมด <?= count($menus) ?> รายการ</small>
        </div>
        <button class="btn btn-danger rounded-pill px-4"
                onclick="openAddModal()">
            <i class="fas fa-plus"></i> เพิ่มเมนูใหม่
        </button>
    </div>

    <!-- กรองหมวดหมู่ -->
    <div class="d-flex gap-2 flex-wrap mb-4">
        <button class="btn btn-danger btn-sm rounded-pill cat-filter active"
                data-cat="all">ทั้งหมด</button>
        <?php foreach ($categories as $cat): ?>
        <button class="btn btn-outline-danger btn-sm rounded-pill cat-filter"
                data-cat="<?= $cat['cat_id'] ?>">
            <?= htmlspecialchars($cat['cat_name']) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- กริดเมนู -->
    <div class="row g-3" id="menu-grid">
        <?php foreach ($menus as $menu): ?>
        <div class="col-6 col-md-3 menu-col"
             data-cat="<?= $menu['cat_id'] ?>">
            <div class="menu-card">
                <?php if (!empty($menu['menu_picture'])): ?>
                    <img src="../assets/images/menu/<?= htmlspecialchars($menu['menu_picture']) ?>"
                         alt="<?= htmlspecialchars($menu['menu_name']) ?>"
                         onerror="this.style.display='none'">
                <?php else: ?>
                    <div class="menu-card-placeholder">🍽️</div>
                <?php endif; ?>

                <div class="menu-card-body">
                    <div class="menu-card-cat">
                        <?= htmlspecialchars($menu['cat_name'] ?? 'ไม่มีหมวด') ?>
                    </div><br>
                    <span class="<?= $menu['is_available'] ? 'badge-avail' : 'badge-unavail' ?>">
                        <?= $menu['is_available'] ? 'มีขาย' : 'หมด' ?>
                    </span>
                    <div class="menu-card-name mt-1">
                        <?= htmlspecialchars($menu['menu_name']) ?>
                    </div>
                    <div class="menu-card-price">
                        <?= number_format($menu['sale_price'], 0) ?> บาท
                    </div>
                    <div class="card-actions">
                        <button class="btn-edit-card"
                                onclick="openEditModal(
                                    '<?= $menu['menu_id'] ?>',
                                    '<?= htmlspecialchars($menu['menu_name'], ENT_QUOTES) ?>',
                                    <?= $menu['sale_price'] ?>,
                                    <?= $menu['cat_id'] ?>,
                                    <?= $menu['is_available'] ?>,
                                    '<?= htmlspecialchars($menu['menu_picture'] ?? '', ENT_QUOTES) ?>'
                                )">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <button class="btn-del-card"
                                onclick="deleteMenu('<?= $menu['menu_id'] ?>', 
                                '<?= htmlspecialchars($menu['menu_name'], ENT_QUOTES) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal เพิ่ม/แก้ไขเมนู -->
<div class="modal fade" id="menuModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">เพิ่มเมนูใหม่</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="edit-menu-id">

                <div class="mb-3">
                    <label class="form-label">รหัสเมนู (เช่น M0011)</label>
                    <input type="text" id="input-menu-id" class="form-control"
                           placeholder="M0011" maxlength="5">
                    <div id="menu-id-hint" class="form-text text-muted"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">ชื่อเมนู</label>
                    <input type="text" id="input-name" class="form-control"
                           placeholder="เช่น ข้าวผัดปู">
                </div>

                <div class="mb-3">
                    <label class="form-label">ราคา (บาท)</label>
                    <input type="number" id="input-price" class="form-control"
                           placeholder="60" min="1">
                </div>

                <div class="mb-3">
                    <label class="form-label">หมวดหมู่</label>
                    <select id="input-cat" class="form-select">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['cat_id'] ?>">
                            <?= htmlspecialchars($cat['cat_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">ชื่อไฟล์รูป (ถ้ามี)</label>
                    <input type="text" id="input-picture" class="form-control"
                           placeholder="เช่น menu01.jpg">
                    <div class="form-text text-muted">
                        วางรูปไว้ที่ assets/images/menu/
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">สถานะ</label>
                    <select id="input-available" class="form-select">
                        <option value="1">มีขาย</option>
                        <option value="0">หมด / ปิดชั่วคราว</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary rounded-pill px-4"
                        data-bs-dismiss="modal">ยกเลิก</button>
                <button class="btn btn-danger rounded-pill px-4"
                        onclick="saveMenu()">
                    <i class="fas fa-save"></i> บันทึก
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('menuModal'));
let isEdit  = false;

// เปิด Modal เพิ่มเมนู
function openAddModal() {
    isEdit = false;
    document.getElementById('modal-title').textContent   = '➕ เพิ่มเมนูใหม่';
    document.getElementById('edit-menu-id').value        = '';
    document.getElementById('input-menu-id').value       = '';
    document.getElementById('input-menu-id').readOnly    = false;
    document.getElementById('input-name').value          = '';
    document.getElementById('input-price').value         = '';
    document.getElementById('input-cat').value           = '';
    document.getElementById('input-picture').value       = '';
    document.getElementById('input-available').value     = '1';
    document.getElementById('menu-id-hint').textContent  = '';
    modal.show();
}

// เปิด Modal แก้ไขเมนู
function openEditModal(id, name, price, cat, avail, pic) {
    isEdit = true;
    document.getElementById('modal-title').textContent   = '✏️ แก้ไขเมนู';
    document.getElementById('edit-menu-id').value        = id;
    document.getElementById('input-menu-id').value       = id;
    document.getElementById('input-menu-id').readOnly    = true;
    document.getElementById('input-name').value          = name;
    document.getElementById('input-price').value         = price;
    document.getElementById('input-cat').value           = cat;
    document.getElementById('input-picture').value       = pic;
    document.getElementById('input-available').value     = avail;
    document.getElementById('menu-id-hint').textContent  = '';
    modal.show();
}

// บันทึกเมนู
function saveMenu() {
    const menu_id   = document.getElementById('input-menu-id').value.trim();
    const name      = document.getElementById('input-name').value.trim();
    const price     = document.getElementById('input-price').value.trim();
    const cat       = document.getElementById('input-cat').value;
    const picture   = document.getElementById('input-picture').value.trim();
    const available = document.getElementById('input-available').value;

    if (!menu_id || !name || !price) {
        alert('กรุณากรอกข้อมูลให้ครบ (รหัส, ชื่อ, ราคา)');
        return;
    }

    fetch('menu_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action    : isEdit ? 'edit' : 'add',
            menu_id   : menu_id,
            menu_name : name,
            sale_price: parseFloat(price),
            cat_id    : parseInt(cat),
            menu_picture: picture,
            is_available: parseInt(available)
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            modal.hide();
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    });
}

// ลบเมนู
function deleteMenu(id, name) {
    if (!confirm('ลบเมนู "' + name + '" ออกจากระบบ?')) return;

    fetch('menu_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', menu_id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    });
}

// กรองหมวดหมู่
document.querySelectorAll('.cat-filter').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.cat-filter').forEach(b => {
            b.classList.remove('active', 'btn-danger');
            b.classList.add('btn-outline-danger');
        });
        this.classList.add('active', 'btn-danger');
        this.classList.remove('btn-outline-danger');

        const cat = this.dataset.cat;
        document.querySelectorAll('.menu-col').forEach(col => {
            col.style.display =
                (cat === 'all' || col.dataset.cat === cat) ? 'block' : 'none';
        });
    });
});
</script>

</body>
</html>