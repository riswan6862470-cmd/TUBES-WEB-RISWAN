<?php
session_start();
require_once 'config/db.php';
if (!isAdmin() && !isUser()) redirect('login.php');

$page_title = 'Profil';
$is_admin = isAdmin();
$msg = '';
$msg_type = '';

if ($is_admin) {
    $admin_id = $_SESSION['admin_id'];
    $data = $conn->query("SELECT * FROM admin WHERE id=$admin_id LIMIT 1")->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_username = sanitize($conn, $_POST['username'] ?? '');
        $new_password = sanitize($conn, $_POST['new_password'] ?? '');
        $current_password = sanitize($conn, $_POST['current_password'] ?? '');

        if (empty($new_username)) {
            $msg = 'Username tidak boleh kosong!';
            $msg_type = 'error';
        } elseif ($data['password'] !== $current_password) {
            $msg = 'Password saat ini tidak sesuai!';
            $msg_type = 'error';
        } else {
            $pass_update = $new_password ? ", password='$new_password'" : '';
            $sql = "UPDATE admin SET username='$new_username' $pass_update WHERE id=$admin_id";
            if ($conn->query($sql)) {
                $_SESSION['admin_username'] = $new_username;
                $data = $conn->query("SELECT * FROM admin WHERE id=$admin_id LIMIT 1")->fetch_assoc();
                $msg = 'Profil berhasil diperbarui!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal memperbarui profil: ' . $conn->error;
                $msg_type = 'error';
            }
        }
    }
} else {
    $user_id = $_SESSION['user_id'];
    $data = $conn->query("SELECT * FROM users WHERE id=$user_id LIMIT 1")->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nama          = sanitize($conn, $_POST['nama'] ?? '');
        $username      = sanitize($conn, $_POST['username'] ?? '');
        $alamat        = sanitize($conn, $_POST['alamat'] ?? '');
        $no_hp         = sanitize($conn, $_POST['no_hp'] ?? '');
        $new_password  = sanitize($conn, $_POST['new_password'] ?? '');
        $current_pass  = sanitize($conn, $_POST['current_password'] ?? '');

        if (empty($nama) || empty($username)) {
            $msg = 'Nama dan username tidak boleh kosong!';
            $msg_type = 'error';
        } elseif ($data['password'] !== $current_pass) {
            $msg = 'Password saat ini tidak sesuai!';
            $msg_type = 'error';
        } else {
            // Check username duplicate
            $check = $conn->query("SELECT id FROM users WHERE username='$username' AND id != $user_id LIMIT 1");
            if ($check && $check->num_rows > 0) {
                $msg = 'Username sudah digunakan!';
                $msg_type = 'error';
            } else {
                $pass_update = $new_password ? ", password='$new_password'" : '';
                $sql = "UPDATE users SET nama='$nama', username='$username', alamat='$alamat', no_hp='$no_hp' $pass_update WHERE id=$user_id";
                if ($conn->query($sql)) {
                    $_SESSION['user_nama'] = $nama;
                    $_SESSION['user_username'] = $username;
                    $data = $conn->query("SELECT * FROM users WHERE id=$user_id LIMIT 1")->fetch_assoc();
                    $msg = 'Profil berhasil diperbarui!';
                    $msg_type = 'success';
                } else {
                    $msg = 'Gagal memperbarui profil: ' . $conn->error;
                    $msg_type = 'error';
                }
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="d-flex">
    <?php include ($is_admin ? 'includes/sidebar_admin.php' : 'includes/sidebar_user.php'); ?>

    <div class="main-content flex-grow-1">
        <div class="topbar">
            <h1 class="topbar-title">Profil <span><?= $is_admin ? 'Admin' : 'Saya' ?></span></h1>
        </div>

        <div class="page-container">
            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb-eco"><a href="<?= $is_admin ? 'dashboard_admin.php' : 'dashboard_user.php' ?>"><i class="bi bi-house-fill"></i> Dashboard</a><i class="bi bi-chevron-right"></i>Profil</div>
                    <h1>Pengaturan Profil</h1>
                    <p>Kelola informasi akun Anda</p>
                </div>
            </div>

            <?php if ($msg): ?>
            <div class="alert-eco <?= $msg_type ?> mb-4">
                <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Profile Card -->
                <div class="col-lg-4">
                    <div class="form-card text-center">
                        <!-- Avatar -->
                        <div style="width:90px;height:90px;background:linear-gradient(135deg,<?= $is_admin ? '#0f172a,#334155' : '#166534,#22c55e' ?>);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;color:#fff;margin:0 auto 16px;">
                            <?php if ($is_admin): ?>
                            <i class="bi bi-person-badge-fill"></i>
                            <?php else: ?>
                            <?= strtoupper(substr($data['nama'] ?? 'U', 0, 1)) ?>
                            <?php endif; ?>
                        </div>

                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($is_admin ? ($data['username'] ?? 'Admin') : ($data['nama'] ?? 'User')) ?></h4>
                        <p style="font-size:13px;color:#64748b;">@<?= htmlspecialchars($data['username']) ?></p>

                        <span class="badge-status <?= $is_admin ? 'badge-b3' : 'badge-normal' ?>">
                            <i class="bi bi-<?= $is_admin ? 'shield-check-fill' : 'person-fill' ?> me-1"></i>
                            <?= $is_admin ? 'Administrator' : 'Warga' ?>
                        </span>

                        <?php if (!$is_admin): ?>
                        <hr>
                        <div class="text-start" style="font-size:13px;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-telephone-fill text-success"></i>
                                <span><?= htmlspecialchars($data['no_hp'] ?? '-') ?></span>
                            </div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-geo-alt-fill text-success mt-1"></i>
                                <span><?= htmlspecialchars($data['alamat'] ?? '-') ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-calendar3 text-success"></i>
                                <span>Bergabung: <?= isset($data['created_at']) ? date('d M Y', strtotime($data['created_at'])) : '-' ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$is_admin): ?>
                    <!-- User Stats -->
                    <div class="form-card mt-3">
                        <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill text-success me-2"></i>Statistik Saya</h6>
                        <?php
                        $uid = $_SESSION['user_id'];
                        $my_total   = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$uid")->fetch_assoc()['c'];
                        $my_selesai = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$uid AND status='SELESAI'")->fetch_assoc()['c'];
                        $my_pending = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$uid AND status='MENUNGGU'")->fetch_assoc()['c'];
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded-3" style="background:#f0fdf4;">
                            <span style="font-size:13px;font-weight:600;">Total Laporan</span>
                            <span class="fw-bold" style="font-size:18px;color:#16a34a;"><?= $my_total ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded-3" style="background:#fffbeb;">
                            <span style="font-size:13px;font-weight:600;">Menunggu</span>
                            <span class="fw-bold" style="font-size:18px;color:#d97706;"><?= $my_pending ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background:#f0fdf4;">
                            <span style="font-size:13px;font-weight:600;">Selesai</span>
                            <span class="fw-bold" style="font-size:18px;color:#16a34a;"><?= $my_selesai ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Edit Form -->
                <div class="col-lg-8">
                    <div class="form-card">
                        <h5 class="fw-bold mb-4"><i class="bi bi-person-gear-fill text-success me-2"></i>Edit Profil</h5>
                        <form method="POST">
                            <div class="row g-3">
                                <?php if (!$is_admin): ?>
                                <div class="col-12">
                                    <label for="nama" class="form-label">Nama Lengkap</label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-person-fill input-icon"></i>
                                        <input type="text" class="form-control" id="nama" name="nama"
                                               value="<?= htmlspecialchars($data['nama']) ?>">
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="<?= $is_admin ? 'col-12' : 'col-md-6' ?>">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-at input-icon"></i>
                                        <input type="text" class="form-control" id="username" name="username"
                                               value="<?= htmlspecialchars($data['username']) ?>">
                                    </div>
                                </div>

                                <?php if (!$is_admin): ?>
                                <div class="col-md-6">
                                    <label for="no_hp" class="form-label">Nomor HP</label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-phone-fill input-icon"></i>
                                        <input type="tel" class="form-control" id="no_hp" name="no_hp"
                                               value="<?= htmlspecialchars($data['no_hp'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-geo-alt-fill input-icon" style="top:14px;transform:none;"></i>
                                        <textarea class="form-control" id="alamat" name="alamat" rows="2"
                                                  style="padding-left:42px;"><?= htmlspecialchars($data['alamat'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="col-12">
                                    <hr style="border-color:#e2e8f0;">
                                    <h6 class="fw-bold mb-3 text-muted"><i class="bi bi-shield-lock-fill me-1"></i>Ubah Password</h6>
                                </div>

                                <div class="col-md-6">
                                    <label for="current_password" class="form-label">Password Saat Ini <span class="text-danger">*</span></label>
                                    <div class="input-icon-wrap" style="position:relative;">
                                        <i class="bi bi-lock-fill input-icon"></i>
                                        <input type="password" class="form-control" id="current_password" name="current_password"
                                               placeholder="Wajib diisi untuk simpan" style="padding-right:44px;">
                                        <button type="button" id="toggleCurrPwd"
                                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;font-size:16px;">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="new_password" class="form-label">Password Baru</label>
                                    <div class="input-icon-wrap" style="position:relative;">
                                        <i class="bi bi-lock-fill input-icon"></i>
                                        <input type="password" class="form-control" id="new_password" name="new_password"
                                               placeholder="Kosongkan jika tidak diubah" style="padding-right:44px;">
                                        <button type="button" id="toggleNewPwd"
                                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;font-size:16px;">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-3 mt-4">
                                <button type="submit" class="btn-eco">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Simpan Perubahan
                                </button>
                                <a href="<?= $is_admin ? 'dashboard_admin.php' : 'dashboard_user.php' ?>" class="btn-outline-eco">
                                    Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
togglePassword('current_password', 'toggleCurrPwd');
togglePassword('new_password', 'toggleNewPwd');
</script>
