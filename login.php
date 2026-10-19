<?php
session_start();

// ถ้า login แล้ว ไปหน้า dashboard เลย
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
        <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ร้านข้าวทองพูน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #fff8f0; font-family: 'Kanit', sans-serif; 
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        .logo {
            text-align: center;
            margin-bottom: 28px;
        }
        .logo .icon { font-size: 60px; }
        .logo h4 {
            font-weight: 500;
            color: #333;
            margin-top: 8px;
        }
        .logo p { color: #888; font-size: 14px; }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1.5px solid #ddd;
            font-size: 15px;
        }
        .form-control:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231,76,60,0.1);
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: #fff5f5;
            border: 1.5px solid #ddd;
            color: #e74c3c;
        }
        .btn-login {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            width: 100%;
            margin-top: 8px;
            box-shadow: 0 4px 15px rgba(231,76,60,0.3);
        }
        .btn-login:hover { opacity: 0.9; color: white; }
        .alert { border-radius: 10px; font-size: 14px; }
    </style>
</head>
<body>

<div class="login-box">
    <div class="logo">
        <img src="/thongpoonrice/assets/images/logo.png" 
         style="height:70px; width:70px; border-radius:50%; object-fit:cover; margin-right:8px;">
        <h4>Thongpoon Rice</h4>
        <p>ระบบจัดการร้าน Thongpoon Rice</p>
    </div>

    <!-- แสดง error ถ้ามี -->
    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php
            if ($_GET['error'] === 'wrong') echo 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            if ($_GET['error'] === 'empty') echo 'กรุณากรอกข้อมูลให้ครบ';
        ?>
    </div>
    <?php endif; ?>

    <form action="login_process.php" method="POST">
        <!-- Username -->
        <div class="mb-3">
            <label class="form-label fw-normal">ชื่อผู้ใช้</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-user"></i>
                </span>
                <input type="text" name="username" class="form-control"
                       placeholder="กรอกชื่อผู้ใช้" required autofocus>
            </div>
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label class="form-label fw-normal">รหัสผ่าน</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
                <input type="password" name="password" class="form-control"
                       placeholder="กรอกรหัสผ่าน" required>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
        </button>
    </form>
</div>

</body>
</html>