<?php
session_start();
require '../connections/db.php';

$table_id = isset($_GET['table']) ? (int)$_GET['table'] : 0;
if ($table_id <= 0) die("❌ ไม่พบข้อมูลโต๊ะ กรุณาสแกน QR Code ใหม่อีกครั้ง");
$_SESSION['table_id'] = $table_id;

$cat_stmt   = $pdo->query("SELECT * FROM category");
$categories = $cat_stmt->fetchAll();

$menu_stmt = $pdo->query("SELECT * FROM menu WHERE is_available = 1");
$menus     = $menu_stmt->fetchAll();

// ตัวเลือกพิเศษของแต่ละเมนู
$extras = [
    'M0001' => [
        ['name' => 'พิเศษข้าวมัน',  'price' => 10],
        ['name' => 'พิเศษไก่',   'price' => 20],
        ['name' => 'พิเศษตับ',   'price' => 20],
        ['name' => 'พิเศษเลือด', 'price' => 10],
    ],
    'M0002' => [
        ['name' => 'พิเศษข้าวมัน',  'price' => 10],
        ['name' => 'พิเศษไก่',   'price' => 20],
        ['name' => 'พิเศษตับ',   'price' => 20],
        ['name' => 'พิเศษเลือด', 'price' => 10],
    ],
    'M0013' => [
        ['name' => 'พิเศษข้าวสวย',  'price' => 10],
        ['name' => 'พิเศษหมูแดง',   'price' => 20],
        ['name' => 'พิเศษกุนเชียง', 'price' => 10],
        ['name' => 'พิเศษไข่เป็ด',  'price' => 15],
        ['name' => 'พิเศษบะหมี่',   'price' => 20],
        ['name' => 'พิเศษเกี๊ยว',   'price' => 20],
    ],
    'M0014' => [
        ['name' => 'พิเศษข้าวสวย',  'price' => 10],
        ['name' => 'พิเศษหมูแดง',   'price' => 20],
        ['name' => 'พิเศษกุนเชียง', 'price' => 10],
        ['name' => 'พิเศษไข่เป็ด',  'price' => 15],
        ['name' => 'พิเศษบะหมี่',   'price' => 20],
        ['name' => 'พิเศษเกี๊ยว',   'price' => 20],
    ],
    'M0015' => [
        ['name' => 'พิเศษข้าวสวย',  'price' => 10],
        ['name' => 'พิเศษหมูแดง',   'price' => 20],
        ['name' => 'พิเศษกุนเชียง', 'price' => 10],
        ['name' => 'พิเศษไข่เป็ด',  'price' => 15],
        ['name' => 'พิเศษบะหมี่',   'price' => 20],
        ['name' => 'พิเศษเกี๊ยว',   'price' => 20],
    ],
    'M0016' => [
        ['name' => 'พิเศษข้าวสวย',  'price' => 10],
        ['name' => 'พิเศษหมูแดง',   'price' => 20],
        ['name' => 'พิเศษกุนเชียง', 'price' => 10],
        ['name' => 'พิเศษไข่เป็ด',  'price' => 15],
        ['name' => 'พิเศษบะหมี่',   'price' => 20],
        ['name' => 'พิเศษเกี๊ยว',   'price' => 20],
    ],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thongpoon Rice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #fff8f0; font-family: 'kanit', sans-serif; }

        .header {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: white; padding: 15px 20px;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .category-bar {
            background: white; padding: 10px 15px;
            overflow-x: auto; white-space: nowrap;
            border-bottom: 2px solid #f0f0f0;
            position: sticky; top: 70px; z-index: 99;
        }
        .category-bar .btn { margin-right: 8px; border-radius: 20px; font-size: 14px; }

        /* การ์ดเมนู */
        .menu-card {
            background: white; border-radius: 12px;
            overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s; margin-bottom: 16px; cursor: pointer;
        }
        .menu-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
        .menu-img { width:100%; height:180px; object-fit:cover; object-position:center top; display:block; }
        @media (max-width:480px) { .menu-img { height:140px; } }
        @media (min-width:768px) { .menu-img { height:200px; } }
        @media (min-width:1024px) { .menu-img { height:320px; } }
        .menu-img-placeholder {
            width:100%; height:180px;
            background:linear-gradient(135deg,#f5f5f5,#e0e0e0);
            display:flex; align-items:center; justify-content:center; font-size:40px;
        }
        .menu-body { padding: 12px; }
        .menu-name { font-size:16px; font-weight:500; color:#333; margin-bottom:4px; }
        .menu-price { color:#e74c3c; font-size:18px; font-weight:500; }
        .btn-add {
            background:#e74c3c; color:white; border:none;
            border-radius:8px; padding:6px 14px; font-size:14px;
            width:100%; margin-top:8px;
        }
        .btn-add:hover { background:#c0392b; color:white; }

        /* ปุ่มตะกร้าลอย */
        .cart-float {
            position:fixed; bottom:25px; right:25px;
            background:#e74c3c; color:white; border:none;
            border-radius:50px; padding:14px 22px; font-size:16px;
            box-shadow:0 4px 15px rgba(231,76,60,0.4); z-index:200; display:none;
        }

        /* === Bottom Sheet Popup === */
        .sheet-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 300;
        }
        .sheet-overlay.show { display: block; }

        .bottom-sheet {
            position: fixed;
            bottom: -100%;
            left: 0; right: 0;
            background: white;
            border-radius: 20px 20px 0 0;
            z-index: 301;
            max-height: 85vh;
            overflow-y: auto;
            transition: bottom 0.35s cubic-bezier(0.32,0.72,0,1);
            padding-bottom: 30px;
        }
        .bottom-sheet.show { bottom: 0; }

        .sheet-img {
            width: 100%; height: 320px;
            object-fit: cover; object-position: center top;
            border-radius: 20px 20px 0 0;
        }
        .sheet-img-placeholder {
            width:100%; height:220px;
            background:linear-gradient(135deg,#f5f5f5,#e0e0e0);
            display:flex; align-items:center; justify-content:center;
            font-size:60px; border-radius:20px 20px 0 0;
        }
        .sheet-handle {
            position: absolute; top: 10px; left: 50%;
            transform: translateX(-50%);
            width: 40px; height: 4px;
            background: rgba(255,255,255,0.7);
            border-radius: 2px;
        }
        .sheet-body { padding: 16px 20px; }
        .sheet-title { font-size: 20px; font-weight: 500; color: #222; }
        .sheet-base-price { font-size: 22px; color: #e74c3c; font-weight: 500; }

        /* ตัวเลือกพิเศษ */
        .section-title {
            font-size: 16px; font-weight: 500; color: #333;
            margin: 16px 0 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #f0f0f0;
        }
        .extra-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
            cursor: pointer;
        }
        .extra-item:last-child { border-bottom: none; }
        .extra-item label { cursor: pointer; font-size: 15px; color: #333; flex: 1; margin-left: 12px; }
        .extra-item .extra-price { color: #e74c3c; font-weight: 500; font-size: 14px; }
        .extra-checkbox {
            width: 22px; height: 22px; border-radius: 6px;
            border: 2px solid #ddd; appearance: none;
            cursor: pointer; transition: all 0.2s; flex-shrink: 0;
            background: white;
        }
        .extra-checkbox:checked {
            background: #e74c3c; border-color: #e74c3c;
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
        }

        /* ปุ่มเพิ่มลงตะกร้าใน sheet */
        .btn-sheet-add {
            position: sticky; bottom: 0;
            background: #e74c3c; color: white;
            border: none; border-radius: 12px;
            padding: 14px; font-size: 16px; font-weight: 500;
            width: calc(100% - 40px); margin: 16px 20px 0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .btn-sheet-add:hover { background: #c0392b; }
    </style>
</head>
<body>

<!-- Overlay -->
<div class="sheet-overlay" id="overlay" onclick="closeSheet()"></div>

<!-- Bottom Sheet -->
<div class="bottom-sheet" id="bottomSheet">
    <div style="position:relative;">
        <div class="sheet-handle"></div>
        <div id="sheet-img-area"></div>
    </div>
    <div class="sheet-body">
        <div class="sheet-title" id="sheet-name"></div>
        <div class="sheet-base-price" id="sheet-price"></div>
        <div id="sheet-extras"></div>
    </div>
    <button class="btn-sheet-add" id="btn-sheet-add" onclick="confirmAdd()">
        <span>เพิ่มลงตะกร้า</span>
        <span id="sheet-total-price"></span>
    </button>
</div>

<!-- Modal เลือกขนาดเครื่องดื่ม -->
<div class="modal fade" id="drinkModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header" style="background:linear-gradient(135deg,#c0392b,#e74c3c);color:white;">
                <h5 class="modal-title">🥤 เลือกขนาด</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-3" id="drink-modal-name"></p>
                <div class="d-flex gap-3">
                    <div class="drink-option flex-fill text-center p-3 rounded-3"
                         style="border:2px solid #ddd;cursor:pointer;" onclick="selectSize('S')">
                        <div style="font-size:36px;">🥤</div>
                        <div class="fw-normal mt-1">S</div>
                        <div class="text-danger fw-normal">19 บาท</div>
                    </div>
                    <div class="drink-option flex-fill text-center p-3 rounded-3"
                         style="border:2px solid #ddd;cursor:pointer;" onclick="selectSize('M')">
                        <div style="font-size:48px;">🥤</div>
                        <div class="fw-normal mt-1">M</div>
                        <div class="text-danger fw-normal">26 บาท</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<div class="header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">
                <img src="/thongpoonrice/assets/images/logo.png"
                     style="height:45px;width:45px;border-radius:50%;object-fit:cover;margin-right:8px;">
                Thongpoon Rice
            </h5>
            <medium>โต๊ะ A<?= $table_id ?></medium>
        </div>
        <div class="text-end"><i class="fas fa-utensils fa-2x"></i></div>
    </div>
</div>

<!-- แถบหมวดหมู่ -->
<div class="category-bar">
    <button class="btn btn-danger btn-sm category-btn active" data-cat="all">ทั้งหมด</button>
    <?php foreach ($categories as $cat): ?>
    <button class="btn btn-outline-danger btn-sm category-btn" data-cat="<?= $cat['cat_id'] ?>">
        <?= htmlspecialchars($cat['cat_name']) ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- รายการเมนู -->
 <!-- ช่องค้นหา -->
<div style="background:white; padding:10px 15px; border-bottom:2px solid #f0f0f0;">
    <div style="position:relative;">
        <i class="fas fa-search" 
           style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#aaa;"></i>
        <input type="text" id="search-input" placeholder="ค้นหาเมนู..."
               style="width:100%; padding:10px 10px 10px 38px; border:1.5px solid #eee;
                      border-radius:25px; font-size:15px; outline:none;"
               oninput="searchMenu(this.value)">
    </div>
</div>
<div class="container-fluid px-3 pt-3">
    <div class="row" id="menu-list">
        <?php foreach ($menus as $menu): ?>
        <?php $menuExtras = $extras[$menu['menu_id']] ?? []; ?>
        <div class="col-6 col-md-4 col-lg-3 menu-item" data-cat="<?= $menu['cat_id'] ?>">
            <div class="menu-card"
                 onclick="openSheet(
                     '<?= $menu['menu_id'] ?>',
                     '<?= htmlspecialchars($menu['menu_name'], ENT_QUOTES) ?>',
                     <?= $menu['sale_price'] ?>,
                     <?= $menu['cat_id'] ?>,
                     '<?= htmlspecialchars($menu['menu_picture'] ?? '', ENT_QUOTES) ?>'
                 )">
                <?php if (!empty($menu['menu_picture'])): ?>
                    <img src="/thongpoonrice/assets/images/menu/<?= htmlspecialchars($menu['menu_picture']) ?>"
                         class="menu-img" alt="<?= htmlspecialchars($menu['menu_name']) ?>">
                <?php else: ?>
                    <div class="menu-img-placeholder">🍽️</div>
                <?php endif; ?>
                <div class="menu-body">
                    <div class="menu-name"><?= htmlspecialchars($menu['menu_name']) ?></div>
                    <div class="menu-price"><?= number_format($menu['sale_price'], 0) ?> บาท</div>
                    <button class="btn-add">
                        <i class="fas fa-plus"></i> เพิ่มลงตะกร้า
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ปุ่มตะกร้าลอย -->
<button class="cart-float" id="cart-btn" onclick="location.href='cart.php'">
    🛒 ดูตะกร้า (<span id="cart-count">0</span> รายการ)
</button>

<!-- ข้อมูลเมนูและตัวเลือกพิเศษ (JSON) -->
<script>
const EXTRAS = <?= json_encode($extras, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ค้นหาเมนู
function searchMenu(keyword) {
    keyword = keyword.trim().toLowerCase();
    let found = 0;

    document.querySelectorAll('.menu-item').forEach(item => {
        const name = item.querySelector('.menu-name').textContent.toLowerCase();
        if (keyword === '' || name.includes(keyword)) {
            item.style.display = 'block';
            found++;
        } else {
            item.style.display = 'none';
        }
    });

    // รีเซ็ต category active กลับเป็นทั้งหมด
    if (keyword === '') {
        document.querySelectorAll('.category-btn').forEach(b => {
            b.classList.remove('active', 'btn-danger');
            b.classList.add('btn-outline-danger');
        });
        document.querySelector('[data-cat="all"]').classList.add('active', 'btn-danger');
        document.querySelector('[data-cat="all"]').classList.remove('btn-outline-danger');
    }

    // แสดงข้อความถ้าไม่พบ
    const emptyMsg = document.getElementById('empty-category');
    if (found === 0) {
        if (!emptyMsg) {
            const div = document.createElement('div');
            div.id = 'empty-category';
            div.className = 'text-center py-5 text-muted col-12';
            div.innerHTML = `<div style="font-size:40px">🔍</div><p>ไม่พบเมนู "${keyword}"</p>`;
            document.getElementById('menu-list').appendChild(div);
        }
    } else {
        if (emptyMsg) emptyMsg.remove();
    }
}
let cart         = JSON.parse(localStorage.getItem('cart')) || [];
let pendingDrink = null;
let currentMenu  = null; // เมนูที่เปิด sheet อยู่
const drinkModal = new bootstrap.Modal(document.getElementById('drinkModal'));
const DRINK_CAT_ID = 5;

// ===== Bottom Sheet =====
function openSheet(id, name, price, catId, picture) {
    // ถ้าเป็นเครื่องดื่ม → เปิด modal เลือกขนาด
    if (parseInt(catId) === DRINK_CAT_ID) {
        pendingDrink = { id, name };
        document.getElementById('drink-modal-name').textContent = name;
        drinkModal.show();
        return;
    }

    currentMenu = { id, name, price: parseInt(price), catId };

    // รูป
    const imgArea = document.getElementById('sheet-img-area');
    if (picture) {
        imgArea.innerHTML = `<img src="/thongpoonrice/assets/images/menu/${picture}" class="sheet-img">`;
    } else {
        imgArea.innerHTML = `<div class="sheet-img-placeholder">🍽️</div>`;
    }

    document.getElementById('sheet-name').textContent  = name;
    document.getElementById('sheet-price').textContent = price + ' บาท';

    // ตัวเลือกพิเศษ
    const extras    = EXTRAS[id] || [];
    const extrasDiv = document.getElementById('sheet-extras');
    if (extras.length > 0) {
        let html = `<div class="section-title">⭐ เพิ่มพิเศษ</div>`;
        extras.forEach((ex, i) => {
            html += `
            <div class="extra-item" onclick="toggleCheck('extra_${i}')">
                <input type="checkbox" class="extra-checkbox"
                       id="extra_${i}"
                       data-price="${ex.price}"
                       onchange="updateTotal()"
                       onclick="event.stopPropagation()">
                <label for="extra_${i}">${ex.name}</label>
                <span class="extra-price">+${ex.price} บาท</span>
            </div>`;
        });
        extrasDiv.innerHTML = html;
    } else {
        extrasDiv.innerHTML = '';
    }

    updateTotal();

    // เปิด sheet
    document.getElementById('overlay').classList.add('show');
    setTimeout(() => document.getElementById('bottomSheet').classList.add('show'), 10);
}

function toggleCheck(id) {
    const cb = document.getElementById(id);
    cb.checked = !cb.checked;
    updateTotal();
}

function closeSheet() {
    document.getElementById('bottomSheet').classList.remove('show');
    document.getElementById('overlay').classList.remove('show');
    currentMenu = null;
}

function updateTotal() {
    if (!currentMenu) return;
    let extra = 0;
    document.querySelectorAll('.extra-checkbox:checked').forEach(cb => {
        extra += parseInt(cb.dataset.price);
    });
    const total = currentMenu.price + extra;
    document.getElementById('sheet-total-price').textContent = total + ' บาท';
}

function confirmAdd() {
    if (!currentMenu) return;

    // เก็บตัวเลือกที่เลือก
    let extraNames  = [];
    let extraPrices = 0;
    document.querySelectorAll('.extra-checkbox:checked').forEach((cb, i) => {
        const label = cb.nextElementSibling.textContent;
        extraNames.push(label);
        extraPrices += parseInt(cb.dataset.price);
    });

    const finalPrice = currentMenu.price + extraPrices;
    const extraText  = extraNames.length > 0 ? ' + ' + extraNames.join(', ') : '';
    const finalName  = currentMenu.name + extraText;
    const finalId    = currentMenu.id + (extraNames.length > 0 ? '_' + extraNames.join('_') : '');

    addItemToCart(finalId, finalName, finalPrice, '');
    closeSheet();
}

// ===== ตะกร้า =====
function updateCartBtn() {
    const total = cart.reduce((sum, item) => sum + item.qty, 0);
    document.getElementById('cart-count').textContent = total;
    document.getElementById('cart-btn').style.display = total > 0 ? 'block' : 'none';
}

function addItemToCart(id, name, price, size) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({ id, name, price, qty: 1, remark: '', size });
    }
    localStorage.setItem('cart', JSON.stringify(cart));
    checkPromotion();
    updateCartBtn();
    showToast(name + ' เพิ่มลงตะกร้าแล้ว ✅');
}

function selectSize(size) {
    if (!pendingDrink) return;
    const price = size === 'S' ? 19 : 26;
    const name  = pendingDrink.name + ' (' + size + ')';
    const id    = pendingDrink.id + '_' + size;
    addItemToCart(id, name, price, size);
    drinkModal.hide();
    pendingDrink = null;
}

// ===== โปรโมชัน =====
function checkPromotion() {
    // นับจำนวนเมนู 59 บาท (ไม่นับตัวเป๊ปซี่ฟรี)
// เมนูที่เข้าเงื่อนไขโปรโมชัน = menu_id ขึ้นต้นด้วย M0001 หรือ M0002 (ข้าวมันไก่/ตับ)
// ไม่ว่าจะเพิ่มพิเศษราคาเท่าไหรก็ตาม
const proMenus = ['M0001', 'M0002'];

const count59 = cart
    .filter(item => {
        const baseId = item.id.split('_')[0]; // ตัด _S, _M, _พิเศษ ออก
        return proMenus.includes(baseId);
    })
    .reduce((sum, item) => sum + item.qty, 0);

const has59 = count59 >= 2;
    const pepsiIndex = cart.findIndex(item => item.id === 'M0023_free');
    if (has59) {
        if (pepsiIndex === -1) {
            cart.push({ id:'M0023_free', name:'🎁 เป๊ปซี่ S (โปรโมชัน)', price:0, qty:1, remark:'แถมฟรี!' });
            showToast('🎁 ได้รับเป๊ปซี่ฟรี! (โปรโมชัน)');
        }
    } else {
        if (pepsiIndex !== -1) cart.splice(pepsiIndex, 1);
    }
    localStorage.setItem('cart', JSON.stringify(cart));
}

// ===== กรองหมวดหมู่ =====
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.category-btn').forEach(b => {
            b.classList.remove('active', 'btn-danger');
            b.classList.add('btn-outline-danger');
        });
        this.classList.add('active', 'btn-danger');
        this.classList.remove('btn-outline-danger');

        const cat   = this.dataset.cat;
        let   found = 0;
        document.querySelectorAll('.menu-item').forEach(item => {
            if (cat === 'all' || item.dataset.cat == cat) {
                item.style.display = 'block'; found++;
            } else {
                item.style.display = 'none';
            }
        });

        const emptyMsg = document.getElementById('empty-category');
        if (found === 0) {
            if (!emptyMsg) {
                const div = document.createElement('div');
                div.id = 'empty-category';
                div.className = 'text-center py-5 text-muted col-12';
                div.innerHTML = '<div style="font-size:40px">🍽️</div><p>ไม่มีเมนูในหมวดนี้</p>';
                document.getElementById('menu-list').appendChild(div);
            }
        } else {
            if (emptyMsg) emptyMsg.remove();
        }
    });
});

// ===== Toast =====
function showToast(msg) {
    const toast = document.createElement('div');
    toast.textContent = msg;
    toast.style.cssText = `
        position:fixed; bottom:90px; left:50%; transform:translateX(-50%);
        background:#333; color:white; padding:10px 20px;
        border-radius:20px; font-size:14px; z-index:999;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
}

updateCartBtn();
</script>

</body>
</html>