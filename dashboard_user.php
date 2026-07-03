<?php
session_start();
require_once 'config/db.php';
if (!isUser()) redirect('login.php');

$page_title = 'Dashboard Warga';
$user_id = $_SESSION['user_id'];

// User's own stats
$my_reports = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$user_id")->fetch_assoc()['c'];
$my_pending = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$user_id AND status='MENUNGGU'")->fetch_assoc()['c'];
$my_done    = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$user_id AND status='SELESAI'")->fetch_assoc()['c'];

// Nearby bins (all bins)
$bins_data = $conn->query("SELECT * FROM bins ORDER BY tingkat_kepenuhan DESC LIMIT 4");

// Today pickups
$today = date('Y-m-d');
$pickups = $conn->query("SELECT * FROM pickup_schedule WHERE tanggal_jemput >= '$today' ORDER BY tanggal_jemput ASC, jam_jemput ASC LIMIT 5");

// My latest reports
$my_latest = $conn->query("SELECT * FROM waste_reports WHERE user_id=$user_id ORDER BY tanggal_laporan DESC LIMIT 5");

// Handle quick report form
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_report'])) {
    $lokasi      = sanitize($conn, $_POST['lokasi'] ?? '');
    $jenis_sampah = sanitize($conn, $_POST['jenis_sampah'] ?? '');
    $deskripsi   = sanitize($conn, $_POST['deskripsi'] ?? '');
    $foto_name   = NULL;

    if (empty($lokasi) || empty($jenis_sampah) || empty($deskripsi)) {
        $msg = 'Semua field wajib diisi!';
        $msg_type = 'error';
    } else {
        // Handle photo upload
        if (!empty($_FILES['foto']['name'])) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
            $file = $_FILES['foto'];
            if (!in_array($file['type'], $allowed_types)) {
                $msg = 'Format foto harus JPG, JPEG, atau PNG!';
                $msg_type = 'error';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $msg = 'Ukuran foto maksimal 5MB!';
                $msg_type = 'error';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $foto_name = 'report_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                move_uploaded_file($file['tmp_name'], $upload_dir . $foto_name);
            }
        }

        if (empty($msg)) {
            $foto_val = $foto_name ? "'$foto_name'" : "NULL";
            $sql = "INSERT INTO waste_reports (user_id, lokasi, jenis_sampah, deskripsi, foto)
                    VALUES ($user_id, '$lokasi', '$jenis_sampah', '$deskripsi', $foto_val)";
            if ($conn->query($sql)) {
                $msg = 'Laporan berhasil dikirim! Tim kami akan segera menindaklanjuti.';
                $msg_type = 'success';
                // Refresh counts
                $my_reports = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$user_id")->fetch_assoc()['c'];
                $my_pending = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$user_id AND status='MENUNGGU'")->fetch_assoc()['c'];
                $my_latest = $conn->query("SELECT * FROM waste_reports WHERE user_id=$user_id ORDER BY tanggal_laporan DESC LIMIT 5");
            } else {
                $msg = 'Gagal mengirim laporan: ' . $conn->error;
                $msg_type = 'error';
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="d-flex">
    <?php include 'includes/sidebar_user.php'; ?>

    <div class="main-content flex-grow-1">
        <!-- Topbar -->
        <div class="topbar">
            <h1 class="topbar-title">Dashboard <span>Warga</span></h1>
            <div class="topbar-right">
                <div class="topbar-badge">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($_SESSION['user_nama']) ?>
                </div>
                <div class="topbar-badge">
                    <i class="bi bi-calendar2-check"></i>
                    <?= date('d M Y') ?>
                </div>
            </div>
        </div>

        <div class="page-container">
            <!-- Welcome Banner -->
            <div class="card-modern p-4 mb-4" style="background:linear-gradient(135deg,#166534,#15803d,#059669);color:#fff;border:none;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1 fw-bold">Halo, <?= htmlspecialchars($_SESSION['user_nama']) ?>! 🌿</h4>
                        <p class="mb-0 opacity-80" style="font-size:14px;">Bersama kita jaga kebersihan lingkungan. Laporkan sampah di sekitar Anda sekarang!</p>
                    </div>
                    <div style="font-size:52px; opacity:0.7;">♻️</div>
                </div>
            </div>

            <?php if ($msg): ?>
            <div class="alert-eco <?= $msg_type ?> mb-4">
                <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
            <?php endif; ?>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card blue">
                        <div class="stat-icon blue"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $my_reports ?>"><?= $my_reports ?></div>
                            <div class="stat-label">Total Laporan Saya</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card orange">
                        <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $my_pending ?>"><?= $my_pending ?></div>
                            <div class="stat-label">Menunggu Tindakan</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card green">
                        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $my_done ?>"><?= $my_done ?></div>
                            <div class="stat-label">Laporan Selesai</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Quick Report Form -->
                <div class="col-lg-5">
                    <div class="form-card">
                        <h5 class="mb-3 fw-bold" style="color:var(--text-main);">
                            <i class="bi bi-file-earmark-plus-fill text-success me-2"></i>
                            Laporan Cepat
                        </h5>
                        <form method="POST" enctype="multipart/form-data" onsubmit="return validateReportForm()">
                            <input type="hidden" name="quick_report" value="1">

                            <div class="mb-3">
                                <label for="lokasi" class="form-label">Lokasi Sampah <span class="text-danger">*</span></label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-geo-alt-fill input-icon"></i>
                                    <input type="text" class="form-control" id="lokasi" name="lokasi"
                                           placeholder="Alamat lengkap lokasi sampah">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="jenis_sampah" class="form-label">Jenis Sampah <span class="text-danger">*</span></label>
                                <select class="form-select" id="jenis_sampah" name="jenis_sampah">
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="Organik">♻️ Organik</option>
                                    <option value="Anorganik">🔵 Anorganik</option>
                                    <option value="B3">⚠️ B3 (Berbahaya)</option>
                                    <option value="Sampah Liar">🚮 Sampah Liar</option>
                                    <option value="Lainnya">📦 Lainnya</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                                          placeholder="Jelaskan kondisi sampah yang ditemukan..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Foto (Opsional)</label>
                                <div class="img-preview-wrap" id="foto-preview" onclick="document.getElementById('foto').click()">
                                    <div class="img-preview-placeholder">
                                        <i class="bi bi-camera-fill d-block mb-2" style="font-size:28px;color:var(--primary);"></i>
                                        <p style="font-size:13px;color:#64748b;margin:0;">Klik untuk upload foto<br><small>JPG, PNG max 5MB</small></p>
                                    </div>
                                </div>
                                <input type="file" id="foto" name="foto" accept="image/jpg,image/jpeg,image/png" style="display:none;">
                            </div>

                            <button type="submit" class="btn-eco w-100 justify-content-center" style="padding:12px;">
                                <i class="bi bi-send-fill"></i>
                                Kirim Laporan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-7">
                    <!-- My Reports -->
                    <div class="table-card mb-4">
                        <div class="table-card-header">
                            <h5 class="table-card-title">
                                <i class="bi bi-clock-history"></i>
                                Riwayat Laporan Saya
                            </h5>
                            <a href="reports.php" class="btn-eco btn-eco-sm">Lihat Semua</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0" style="font-size:13px;">
                                <thead>
                                    <tr>
                                        <th>Lokasi</th>
                                        <th>Jenis</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($my_latest && $my_latest->num_rows > 0):
                                        while ($r = $my_latest->fetch_assoc()):
                                            $st = strtolower($r['status']);
                                            $badge = ['menunggu'=>'badge-menunggu','diproses'=>'badge-diproses','selesai'=>'badge-selesai'][$st] ?? 'badge-menunggu';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars(substr($r['lokasi'], 0, 22)) ?>...</td>
                                        <td><?= htmlspecialchars($r['jenis_sampah']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($r['tanggal_laporan'])) ?></td>
                                        <td><span class="badge-status <?= $badge ?>"><?= $r['status'] ?></span></td>
                                    </tr>
                                    <?php endwhile;
                                    else: ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox d-block mb-1" style="font-size:24px;"></i>
                                        Belum ada laporan
                                    </td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Upcoming Pickups -->
                    <div class="table-card">
                        <div class="table-card-header">
                            <h5 class="table-card-title">
                                <i class="bi bi-truck-front-fill"></i>
                                Jadwal Penjemputan
                            </h5>
                            <a href="pickup.php" class="btn-eco btn-eco-sm">Detail</a>
                        </div>
                        <div class="p-3">
                            <?php if ($pickups && $pickups->num_rows > 0):
                                while ($p = $pickups->fetch_assoc()):
                                    $is_today = $p['tanggal_jemput'] == $today;
                                    $st = strtolower($p['status']);
                                    $badge = ['menunggu'=>'badge-menunggu','dijemput'=>'badge-dijemput','selesai'=>'badge-selesai'][$st] ?? 'badge-menunggu';
                            ?>
                            <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded-3" style="background:<?= $is_today ? '#f0fdf4' : '#f8fafc' ?>;border:1px solid <?= $is_today ? '#bbf7d0' : '#e2e8f0' ?>;">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="text-align:center;min-width:44px;">
                                        <div style="font-size:10px;text-transform:uppercase;color:#64748b;font-weight:600;"><?= date('M', strtotime($p['tanggal_jemput'])) ?></div>
                                        <div style="font-size:20px;font-weight:800;color:<?= $is_today ? '#16a34a' : '#374151' ?>;"><?= date('d', strtotime($p['tanggal_jemput'])) ?></div>
                                    </div>
                                    <div>
                                        <div style="font-size:13px;font-weight:600;color:#374151;"><?= htmlspecialchars($p['lokasi']) ?></div>
                                        <div style="font-size:12px;color:#64748b;"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($p['jam_jemput'])) ?> WIB</div>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-1">
                                    <span class="badge-status <?= $badge ?>"><?= $p['status'] ?></span>
                                    <?php if ($is_today): ?><small style="color:#16a34a;font-size:11px;font-weight:600;">Hari Ini</small><?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile;
                            else: ?>
                            <div class="empty-state">
                                <i class="bi bi-truck-front"></i>
                                <p>Belum ada jadwal penjemputan</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bins Status Section -->
            <div class="table-card mt-4">
                <div class="table-card-header">
                    <h5 class="table-card-title"><i class="bi bi-box-fill"></i>Status Bin di Sekitar Anda</h5>
                    <a href="bins.php" class="btn-eco btn-eco-sm">Lihat Semua</a>
                </div>
                <div class="row g-3 p-3">
                    <?php
                    // Reset bins query
                    $bins_data = $conn->query("SELECT * FROM bins ORDER BY tingkat_kepenuhan DESC LIMIT 4");
                    if ($bins_data && $bins_data->num_rows > 0):
                        while ($bin = $bins_data->fetch_assoc()):
                            $pct = min(100, intval($bin['tingkat_kepenuhan']));
                            $is_full = $pct >= 80;
                    ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="bin-card <?= strtolower($bin['status']) ?>">
                            <div class="bin-card-icon <?= strtolower($bin['status']) ?>">
                                <i class="bi bi-trash<?= $is_full ? '3-fill' : '3' ?>"></i>
                            </div>
                            <div class="fw-600 mb-1" style="font-size:13px;"><?= htmlspecialchars(substr($bin['lokasi'], 0, 25)) ?>...</div>
                            <div class="bin-progress mb-2">
                                <div class="bin-progress-bar <?= $is_full ? 'penuh' : 'normal' ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted"><?= $pct ?>% penuh</small>
                                <span class="badge-status <?= $is_full ? 'badge-penuh' : 'badge-normal' ?>"><?= $bin['status'] ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile;
                    else: ?>
                    <div class="col-12"><p class="text-center text-muted py-3">Belum ada data bin</p></div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- .page-container -->
    </div><!-- .main-content -->
</div>

<?php include 'includes/footer.php'; ?>
