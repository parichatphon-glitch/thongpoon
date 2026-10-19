<?php
session_start();
require '../connections/db.php';

$table_id = $_SESSION['table_id'] ?? 0;
if ($table_id <= 0) {
    header("Location: index.php?table=1");
    exit;
}

// ดึงเมนูที่เข้าร่วมโปรโมชัน (ราคา 59 บาท)
$promo_stmt  = $pdo->query("SELECT menu_id FROM menu WHERE sale_price = 59 AND is_available = 1");
$promo_menus = array_column($promo_stmt->fetchAll(), 'menu_id');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - ร้านข้าวทองพูน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #fff8f0; font-family: 'Kanit', sans-serif; }

        .header {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: white; padding: 15px 20px;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .cart-item {
            background: white; border-radius: 12px;
            padding: 14px; margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .item-name { font-weight: 500; font-size: 16px; color: #333; }
        .item-price { color: #e74c3c; font-weight: 500; }
        .qty-btn {
            width: 32px; height: 32px; border-radius: 50%;
            border: 2px solid #e74c3c; background: white;
            color: #e74c3c; font-size: 18px; line-height: 1;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
        }
        .qty-btn:hover { background: #e74c3c; color: white; }
        .qty-number { width: 36px; text-align: center; font-size: 16px; font-weight: 500; }
        .remark-input {
            border: 1px solid #ddd; border-radius: 8px;
            padding: 5px 10px; font-size: 13px;
            width: 100%; margin-top: 6px; color: #666;
        }
        .btn-remove { background: none; border: none; color: #bbb; font-size: 18px; cursor: pointer; }
        .btn-remove:hover { color: #e74c3c; }
        .promo-item {
            background: #fff9e6; border: 1.5px dashed #ffc107;
            border-radius: 12px; padding: 12px 14px;
            margin-bottom: 12px;
        }
        .summary-box {
            background: white; border-radius: 12px;
            padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .btn-confirm {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: white; border: none; border-radius: 12px;
            padding: 14px; font-size: 18px; width: 100%;
            margin-top: 12px; box-shadow: 0 4px 15px rgba(231,76,60,0.3);
        }
        .btn-confirm:hover { opacity: 0.9; color: white; }
        .empty-cart { text-align: center; padding: 60px 20px; color: #aaa; }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="d-flex align-items-center gap-3">
        <a href="index.php?table=<?= $table_id ?>" class="text-white">
            <i class="fas fa-arrow-left fa-lg"></i>
        </a>
        <div>
            <h5 class="mb-0">🛒 ตะกร้าสินค้า</h5>
            <small>โต๊ะ A<?= $table_id ?></small>
        </div>
    </div>
</div>

<div class="container-fluid px-3 pt-3">
    <div id="cart-list"></div>

    <div id="empty-msg" class="empty-cart" style="display:none;">
        <div style="font-size:60px;">🛒</div>
        <p>ยังไม่มีรายการอาหาร</p>
        <a href="index.php?table=<?= $table_id ?>" class="btn btn-danger rounded-pill px-4">
            เลือกเมนูอาหาร
        </a>
    </div>

    <div id="summary-section">
        <div class="summary-box">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">รวมทั้งหมด</span>
                <span id="total-price" class="fw-normal fs-5 text-danger">0 บาท</span>
            </div>
        </div>
        <button class="btn-confirm" onclick="goToPayment()">
            <i class="fas fa-check-circle"></i> ยืนยันการสั่งอาหาร
        </button>
        <a href="index.php?table=<?= $table_id ?>"
           class="btn btn-outline-secondary w-100 mt-2 rounded-pill">
            <i class="fas fa-plus"></i> เพิ่มเมนูอีก
        </a>
    </div>
</div>

<script>
let cart = JSON.parse(localStorage.getItem('cart')) || [];
const PROMO_MENUS = <?= json_encode($promo_menus) ?>;

// เช็คโปรโมชัน
function checkPromotion() {
    const count59 = cart
        .filter(item => {
            const baseId = item.id.split('_')[0];
            return PROMO_MENUS.includes(baseId);
        })
        .reduce((sum, item) => sum + item.qty, 0);

    const pepsiIndex = cart.findIndex(item => item.id === 'M0023_free');

    if (count59 >= 2) {
        if (pepsiIndex === -1) {
            cart.push({
                id: 'M0023_free',
                name: '🎁 เป๊ปซี่(โปรโมชัน)',
                price: 0, qty: 1,
                remark: 'แถมฟรี! ซื้อเมนู 59 บาท 2 จาน'
            });
        }
    } else {
        if (pepsiIndex !== -1) cart.splice(pepsiIndex, 1);
    }
    localStorage.setItem('cart', JSON.stringify(cart));
}

// แสดงรายการในตะกร้า
function renderCart() {
    const list    = document.getElementById('cart-list');
    const empty   = document.getElementById('empty-msg');
    const summary = document.getElementById('summary-section');

    if (cart.length === 0) {
        list.innerHTML = '';
        empty.style.display   = 'block';
        summary.style.display = 'none';
        return;
    }

    empty.style.display   = 'none';
    summary.style.display = 'block';

    let html  = '';
    let total = 0;

    cart.forEach((item, index) => {
        const subtotal = item.price * item.qty;
        total += subtotal;

        // ถ้าเป็นรายการโปรโมชัน แสดงแบบพิเศษ
        if (item.id === 'M0023_free') {
            html += `
            <div class="promo-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-normal">${item.name}</div>
                    <small class="text-warning">🎉 ${item.remark}</small>
                </div>
                <div class="fw-normal text-success">ฟรี!</div>
            </div>`;
            return;
        }

        html += `
        <div class="cart-item">
            <div class="d-flex justify-content-between align-items-start">
                <div class="item-name">${item.name}</div>
                <button class="btn-remove" onclick="removeItem(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="item-price">${item.price.toFixed(0)} บาท / จาน</div>
            <div class="d-flex align-items-center gap-2 mt-2">
                <button class="qty-btn" onclick="changeQty(${index}, -1)">−</button>
                <span class="qty-number">${item.qty}</span>
                <button class="qty-btn" onclick="changeQty(${index}, +1)">+</button>
                <span class="ms-auto fw-normal text-dark">${subtotal.toFixed(0)} บาท</span>
            </div>
            <input class="remark-input" type="text"
                   placeholder="หมายเหตุ เช่น ไม่ใส่ผัก, ไม่กระเทียม"
                   value="${item.remark || ''}"
                   onchange="updateRemark(${index}, this.value)">
        </div>`;
    });

    list.innerHTML = html;
    document.getElementById('total-price').textContent = total.toLocaleString() + ' บาท';
}

// เปลี่ยนจำนวน
function changeQty(index, change) {
    cart[index].qty += change;
    if (cart[index].qty <= 0) cart.splice(index, 1);
    saveCart();
}

// ลบรายการ
function removeItem(index) {
    cart.splice(index, 1);
    saveCart();
}

// อัปเดตหมายเหตุ
function updateRemark(index, value) {
    cart[index].remark = value;
    saveCart();
}

// บันทึกตะกร้า
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    checkPromotion();
    renderCart();
}

// ไปหน้าชำระเงิน
function goToPayment() {
    if (cart.length === 0) return;
    location.href = 'payment.php';
}

// โหลดหน้า
checkPromotion();
renderCart();
</script>

</body>
</html>