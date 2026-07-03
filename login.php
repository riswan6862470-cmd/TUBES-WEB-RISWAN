<?php
session_start();
require_once 'config/db.php';

// If already logged in, redirect
if (isset($_SESSION['admin_id'])) {
    redirect('dashboard_admin.php');
}
if (isset($_SESSION['user_id'])) {
    redirect('dashboard_user.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = sanitize($conn, $_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        // Check admin first
        $sql_admin = "SELECT * FROM admin WHERE username = '$username' AND password = '$password' LIMIT 1";
        $res_admin = $conn->query($sql_admin);

        if ($res_admin && $res_admin->num_rows > 0) {
            $admin = $res_admin->fetch_assoc();
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['role'] = 'admin';
            redirect('dashboard_admin.php');
        } else {
            // Check user
            $sql_user = "SELECT * FROM users WHERE username = '$username' AND password = '$password' LIMIT 1";
            $res_user = $conn->query($sql_user);

            if ($res_user && $res_user->num_rows > 0) {
                $user = $res_user->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['role'] = 'user';
                redirect('dashboard_user.php');
            } else {
                $error = 'Username atau password salah. Silakan coba lagi.';
            }
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Smart Waste Management System - Login">
    <title>Login | Smart Waste Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="/TUBES WEB RISWAN/assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">

    <!-- Floating Particles -->
    <?php for ($i = 0; $i < 12; $i++): 
        $size = rand(4, 16);
        $left = rand(0, 100);
        $delay = rand(0, 15);
        $duration = rand(12, 25);
        $colors = ['#22c55e', '#16a34a', '#059669', '#0d9488', '#15803d'];
        $color = $colors[array_rand($colors)];
    ?>
    <div class="particle" style="
        width:<?=$size?>px; height:<?=$size?>px;
        left:<?=$left?>%;
        animation-duration:<?=$duration?>s;
        animation-delay:<?=$delay?>s;
        background:<?=$color?>;
    "></div>
    <?php endfor; ?>

    <div class="auth-card">
        <!-- Logo -->
        <div class="auth-logo">
            <div class="auth-logo-icon">
                <i class="bi bi-recycle"></i>
            </div>
            <div class="auth-logo-text">
                <div class="auth-logo-title">SmartWaste</div>
                <div class="auth-logo-sub">Management System</div>
            </div>
        </div>

        <h2 class="auth-title">Selamat Datang 👋</h2>
        <p class="auth-subtitle">Masuk ke akun Anda untuk melanjutkan</p>

        <!-- Alert Error -->
        <?php if ($error): ?>
        <div class="alert-eco error mb-4">
            <i class="bi bi-x-circle-fill"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
        <div class="alert-eco success mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <span>Registrasi berhasil! Silakan login.</span>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout'])): ?>
        <div class="alert-eco info mb-4">
            <i class="bi bi-info-circle-fill"></i>
            <span>Anda telah berhasil logout.</span>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" onsubmit="return validateLoginForm()">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-person-fill input-icon"></i>
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="Masukkan username" autocomplete="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-icon-wrap" style="position:relative;">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Masukkan password" autocomplete="current-password"
                           style="padding-right: 44px;">
                    <button type="button" id="togglePwd"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;font-size:16px;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-eco w-100 justify-content-center" style="padding:12px;">
                <i class="bi bi-box-arrow-in-right"></i>
                Masuk
            </button>
        </form>

        <div class="auth-divider">atau</div>

        <a href="/TUBES WEB RISWAN/register.php" class="btn-outline-eco w-100 justify-content-center" style="padding:11px;">
            <i class="bi bi-person-plus-fill"></i>
            Daftar sebagai Warga
        </a>

        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/TUBES WEB RISWAN/assets/js/main.js"></script>
    <script>
        togglePassword('password', 'togglePwd');
    </script>
</body>
</html>
