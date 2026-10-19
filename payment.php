<?php
session_start();
require '../connections/db.php';

$table_id = $_SESSION['table_id'] ?? 0;
if ($table_id <= 0) {
    header("Location: index.php?table=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน - ร้านข้าวทองพูน</title>
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
        .section-box {
            background: white; border-radius: 12px;
            padding: 16px; margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .section-title {
            font-weight: 500; font-size: 16px; color: #333;
            border-left: 4px solid #e74c3c;
            padding-left: 10px; margin-bottom: 12px;
        }
        .order-row {
            display: flex; justify-content: space-between;
            padding: 6px 0; border-bottom: 1px dashed #f0f0f0; font-size: 15px;
        }
        .order-row:last-child { border-bottom: none; }
        .pay-option {
            border: 2px solid #ddd; border-radius: 12px;
            padding: 14px; cursor: pointer; transition: all 0.2s;
            margin-bottom: 10px; display: flex; align-items: center; gap: 12px;
        }
        .pay-option:hover { border-color: #e74c3c; }
        .pay-option.selected { border-color: #e74c3c; background: #fff5f5; }
        .pay-option .icon { font-size: 28px; width: 40px; text-align: center; }
        .pay-option .label { font-weight: 500; font-size: 15px; }
        .pay-option .desc { font-size: 12px; color: #888; }
        #qr-section { text-align: center; padding: 10px 0; display: none; }
        #qr-section img {
            width: 220px; height: 220px;
            border: 2px solid #ddd; border-radius: 12px; padding: 8px;
        }
        .qr-amount { font-size: 22px; font-weight: 500; color: #e74c3c; margin: 8px 0; }
        #cash-section { text-align: center; padding: 10px 0; display: none; }
        .btn-confirm {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: white; border: none; border-radius: 12px;
            padding: 14px; font-size: 18px; width: 100%;
            box-shadow: 0 4px 15px rgba(231,76,60,0.3);
        }
        .btn-confirm:disabled { background: #ccc; box-shadow: none; }
        .dine-opt {
            border: 2px solid #ddd; border-radius: 12px;
            padding: 14px; cursor: pointer; transition: all 0.2s;
            text-align: center; flex: 1;
        }
        .dine-opt:hover { border-color: #e74c3c; }
        .dine-opt.selected { border-color: #e74c3c; background: #fff0f0; }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="d-flex align-items-center gap-3">
        <a href="cart.php" class="text-white">
            <i class="fas fa-arrow-left fa-lg"></i>
        </a>
        <div>
            <h5 class="mb-0">💳 ชำระเงิน</h5>
            <small>โต๊ะ A<?= $table_id ?></small>
        </div>
    </div>
</div>

<div class="container-fluid px-3 pt-3">

    <!-- สรุปรายการ -->
    <div class="section-box">
        <div class="section-title">📋 สรุปรายการอาหาร</div>
        <div id="order-summary"></div>
        <div class="d-flex justify-content-between mt-3 pt-2"
             style="border-top: 2px solid #f0f0f0;">
            <span class="fw-normal fs-5">รวมทั้งหมด</span>
            <span class="fw-normal fs-5 text-danger" id="total-display">0 บาท</span>
        </div>
    </div>

    <!-- เลือกทานที่ร้าน / กลับบ้าน -->
    <div class="section-box">
        <div class="section-title">🍽️ รับอาหารแบบไหน?</div>
        <div class="d-flex gap-3">
            <div class="dine-opt" id="opt-dine" onclick="selectDineType('dine_in')">
                <div style="font-size:30px;">🪑</div>
                <div class="fw-normal mt-1" style="font-size:14px;">ทานที่ร้าน</div>
            </div>
            <div class="dine-opt" id="opt-takeaway" onclick="selectDineType('takeaway')">
                <div style="font-size:30px;">🏠</div>
                <div class="fw-normal mt-1" style="font-size:14px;">กลับบ้าน (+6 บาท)</div>
            </div>
        </div>
    </div>

    <!-- เลือกวิธีชำระเงิน -->
    <div class="section-box">
        <div class="section-title">💰 เลือกวิธีชำระเงิน</div>

        <div class="pay-option" onclick="selectPayment('qr')" id="opt-qr">
            <div class="icon">📱</div>
            <div>
                <div class="label">QR PromptPay</div>
                <div class="desc">สแกนจ่ายผ่านแอปธนาคาร</div>
            </div>
        </div>

        <div class="pay-option" onclick="selectPayment('cash')" id="opt-cash">
            <div class="icon">💵</div>
            <div>
                <div class="label">เงินสด</div>
            </div>
        </div>

        <div id="qr-section">
            <p class="text-muted mb-2">สแกน QR Code เพื่อชำระเงิน</p>
            <img src="../assets/images/77.jpg" alt="QR PromptPay"
                 onerror="this.src='https://via.placeholder.com/220x220?text=QR+PromptPay'">
            <div class="qr-amount" id="qr-amount">0 บาท</div>
            <p class="text-muted small">ชำระแล้วกดปุ่มด้านล่างได้เลย</p>
        </div>

        <div id="cash-section">
            <div class="alert alert-warning rounded-3">
                <i class="fas fa-info-circle"></i> กรุณาชำระที่เคาน์เตอร์
            </div>
        </div>
    </div>

    <!-- ปุ่มยืนยัน -->
    <button class="btn-confirm" id="btn-confirm" onclick="submitOrder()" disabled>
        <i class="fas fa-check-circle"></i> ยืนยันการสั่งและชำระเงิน
    </button>
    <div style="height:30px;"></div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let cart      = JSON.parse(localStorage.getItem('cart')) || [];
let payMethod = null;
let dineType  = null;
const tableId = <?= $table_id ?>;

// แสดงสรุปรายการ
function renderSummary() {
    let html  = '';
    let total = 0;
    cart.forEach(item => {
        const sub = item.price * item.qty;
        total += sub;
        html += `
        <div class="order-row">
            <span>${item.name}
                <span class="text-muted">x${item.qty}</span>
                ${item.remark ? '<br><small class="text-muted">📝 ' + item.remark + '</small>' : ''}
            </span>
            <span class="fw-normal">${sub.toFixed(0)} บาท</span>
        </div>`;
    });
    document.getElementById('order-summary').innerHTML = html;

    const takeawayFee = (dineType === 'takeaway') ? 6 : 0;
    const grandTotal  = total + takeawayFee;

    document.getElementById('total-display').textContent = grandTotal.toLocaleString() + ' บาท';
    document.getElementById('qr-amount').textContent     = grandTotal.toLocaleString() + ' บาท';

    const feeMsg = document.getElementById('takeaway-fee');
    if (takeawayFee > 0) {
        if (!feeMsg) {
            const div = document.createElement('div');
            div.id = 'takeaway-fee';
            div.className = 'text-muted mt-1';
            div.style.fontSize = '13px';
            div.textContent = '🏠 กลับบ้าน +6 บาท';
            document.getElementById('total-display').parentElement.appendChild(div);
        }
    } else {
        if (feeMsg) feeMsg.remove();
    }
}

// เลือกทานที่ร้าน / กลับบ้าน
function selectDineType(type) {
    dineType = type;
    document.getElementById('opt-dine').classList.remove('selected');
    document.getElementById('opt-takeaway').classList.remove('selected');
    if (type === 'dine_in') {
        document.getElementById('opt-dine').classList.add('selected');
    } else {
        document.getElementById('opt-takeaway').classList.add('selected');
    }
    renderSummary();
    checkReady();
}

// เลือกวิธีจ่าย
function selectPayment(method) {
    payMethod = method;
    document.getElementById('opt-qr').classList.remove('selected');
    document.getElementById('opt-cash').classList.remove('selected');
    document.getElementById('qr-section').style.display   = 'none';
    document.getElementById('cash-section').style.display = 'none';
    if (method === 'qr') {
        document.getElementById('opt-qr').classList.add('selected');
        document.getElementById('qr-section').style.display = 'block';
    } else {
        document.getElementById('opt-cash').classList.add('selected');
        document.getElementById('cash-section').style.display = 'block';
    }
    checkReady();
}

// เช็คพร้อมยืนยันมั้ย
function checkReady() {
    document.getElementById('btn-confirm').disabled = !(payMethod && dineType);
}

// ส่งออเดอร์
function submitOrder() {
    if (cart.length === 0 || !payMethod || !dineType) return;

    document.getElementById('btn-confirm').disabled    = true;
    document.getElementById('btn-confirm').textContent = '⏳ กำลังบันทึก...';

    const takeawayFee = (dineType === 'takeaway') ? 6 : 0;
    const hasPromo    = cart.some(item => item.id === 'M0023_free');

    fetch('save_order.php', {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({
            table_id     : parseInt(tableId),
            pay_method   : payMethod,
            dine_type    : dineType,
            takeaway_fee : takeawayFee,
            pro_id       : hasPromo ? 1 : null,
            cart         : cart
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            localStorage.removeItem('cart');
            location.href = 'success.php?order_id=' + data.order_id;
        } else {
            alert('❌ เกิดข้อผิดพลาด: ' + data.message);
            document.getElementById('btn-confirm').disabled = false;
            document.getElementById('btn-confirm').innerHTML =
                '<i class="fas fa-check-circle"></i> ยืนยันการสั่งและชำระเงิน';
        }
    })
    .catch(err => {
        alert('❌ ไม่สามารถเชื่อมต่อได้: ' + err);
    });
}

renderSummary();
</script>

</body>
</html>