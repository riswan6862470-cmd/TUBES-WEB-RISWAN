<?php
session_start();
require_once 'config/db.php';

// If already logged in
if (isset($_SESSION['admin_id'])) redirect('dashboard_admin.php');
if (isset($_SESSION['user_id'])) redirect('dashboard_user.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = sanitize($conn, $_POST['nama'] ?? '');
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = sanitize($conn, $_POST['password'] ?? '');
    $alamat   = sanitize($conn, $_POST['alamat'] ?? '');
    $no_hp    = sanitize($conn, $_POST['no_hp'] ?? '');

    if (empty($nama) || empty($username) || empty($password) || empty($alamat) || empty($no_hp)) {
        $error = 'Semua field wajib diisi!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Check duplicate username
        $check = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $error = 'Username sudah digunakan! Pilih username lain.';
        } else {
            $sql = "INSERT INTO users (nama, username, password, alamat, no_hp)
                    VALUES ('$nama', '$username', '$password', '$alamat', '$no_hp')";
            if ($conn->query($sql)) {
                redirect('login.php?registered=1');
            } else {
                $error = 'Gagal mendaftar. Silakan coba lagi. Error: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Smart Waste Management System - Registrasi Warga">
    <title>Daftar | Smart Waste Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">

    <!-- Floating Particles -->
    <?php for ($i = 0; $i < 10; $i++): 
        $size = rand(4, 14);
        $left = rand(0, 100);
        $delay = rand(0, 15);
        $duration = rand(10, 22);
        $colors = ['#22c55e', '#16a34a', '#059669', '#0d9488'];
        $color = $colors[array_rand($colors)];
    ?>
    <div class="particle" style="width:<?=$size?>px;height:<?=$size?>px;left:<?=$left?>%;animation-duration:<?=$duration?>s;animation-delay:<?=$delay?>s;background:<?=$color?>;"></div>
    <?php endfor; ?>

    <div class="auth-card" style="max-width:520px;">
        <!-- Logo -->
        <div class="auth-logo">
            <div class="auth-logo-icon">
                <i class="bi bi-recycle"></i>
            </div>
            <div class="auth-logo-text">
                <div class="auth-logo-title">SmartWaste</div>
                <div class="auth-logo-sub">Daftar Akun Warga</div>
            </div>
        </div>

        <h2 class="auth-title">Buat Akun Baru 🌱</h2>
        <p class="auth-subtitle">Daftarkan diri Anda untuk mulai melaporkan sampah di sekitar Anda</p>

        <?php if ($error): ?>
        <div class="alert-eco error mb-4">
            <i class="bi bi-x-circle-fill"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="" onsubmit="return validateRegisterForm()">
            <div class="row g-3">
                <div class="col-12">
                    <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-person-fill input-icon"></i>
                        <input type="text" class="form-control" id="nama" name="nama"
                               placeholder="Nama lengkap Anda"
                               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-at input-icon"></i>
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Buat username unik"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-icon-wrap" style="position:relative;">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Min. 6 karakter" style="padding-right:44px;">
                        <button type="button" id="togglePwd"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;font-size:16px;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12">
                    <label for="no_hp" class="form-label">Nomor HP <span class="text-danger">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-phone-fill input-icon"></i>
                        <input type="tel" class="form-control" id="no_hp" name="no_hp"
                               placeholder="08xxxxxxxxxx"
                               value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-12">
                    <label for="alamat" class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-geo-alt-fill input-icon" style="top:14px;transform:none;"></i>
                        <textarea class="form-control" id="alamat" name="alamat" rows="2"
                                  placeholder="Jl. Contoh No. 1, Kota"
                                  style="padding-left:42px;"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-eco w-100 justify-content-center mt-4" style="padding:12px;">
                <i class="bi bi-person-check-fill"></i>
                Daftar Sekarang
            </button>
        </form>

        <div class="auth-divider">sudah punya akun?</div>

        <a href="login.php" class="btn-outline-eco w-100 justify-content-center" style="padding:11px;">
            <i class="bi bi-box-arrow-in-right"></i>
            Masuk ke Akun
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        togglePassword('password', 'togglePwd');
    </script>
</body>
</html>
