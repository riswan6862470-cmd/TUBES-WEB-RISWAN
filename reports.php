<?php
session_start();
require_once 'config/db.php';
if (!isAdmin() && !isUser()) redirect('login.php');

$page_title = 'Laporan Warga';
$is_admin = isAdmin();
$user_id = $_SESSION['user_id'] ?? 0;
$msg = '';
$msg_type = '';

// Admin: update status
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $id = intval($_POST['id'] ?? 0);
    $status = sanitize($conn, $_POST['status'] ?? '');
    $allowed = ['MENUNGGU', 'DIPROSES', 'SELESAI'];
    if ($id > 0 && in_array($status, $allowed)) {
        if ($conn->query("UPDATE waste_reports SET status='$status' WHERE id=$id")) {
            $msg = 'Status laporan berhasil diperbarui!';
            $msg_type = 'success';
        } else {
            $msg = 'Gagal memperbarui status!';
            $msg_type = 'error';
        }
    }
}

// Admin: delete report
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        // Delete photo if exists
        $r = $conn->query("SELECT foto FROM waste_reports WHERE id=$id LIMIT 1");
        if ($r && $r->num_rows > 0) {
            $row = $r->fetch_assoc();
            if ($row['foto']) {
                $file_path = __DIR__ . '/uploads/' . $row['foto'];
                if (file_exists($file_path)) unlink($file_path);
            }
        }
        if ($conn->query("DELETE FROM waste_reports WHERE id=$id")) {
            $msg = 'Laporan berhasil dihapus!';
            $msg_type = 'success';
        }
    }
}

// User: add report
if (!$is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $lokasi      = sanitize($conn, $_POST['lokasi'] ?? '');
    $jenis_sampah = sanitize($conn, $_POST['jenis_sampah'] ?? '');
    $deskripsi   = sanitize($conn, $_POST['deskripsi'] ?? '');
    $foto_name   = NULL;

    if (empty($lokasi) || empty($jenis_sampah) || empty($deskripsi)) {
        $msg = 'Semua field wajib diisi!';
        $msg_type = 'error';
    } else {
        if (!empty($_FILES['foto']['name'])) {
            $file = $_FILES['foto'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($file['type'], $allowed_types)) {
                $msg = 'Format foto harus JPG/JPEG/PNG!';
                $msg_type = 'error';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $msg = 'Ukuran foto maksimal 5MB!';
                $msg_type = 'error';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
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
                $msg = 'Laporan berhasil dikirim!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal mengirim laporan: ' . $conn->error;
                $msg_type = 'error';
            }
        }
    }
}

// Fetch reports
$filter_status = isset($_GET['status']) ? sanitize($conn, $_GET['status']) : '';

if ($is_admin) {
    $where = $filter_status ? "WHERE wr.status='$filter_status'" : '';
    $reports = $conn->query("
        SELECT wr.*, u.nama as user_nama, u.no_hp
        FROM waste_reports wr
        JOIN users u ON wr.user_id = u.id
        $where
        ORDER BY wr.tanggal_laporan DESC
    ");
    $total = $conn->query("SELECT COUNT(*) as c FROM waste_reports")->fetch_assoc()['c'];
    $menunggu = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE status='MENUNGGU'")->fetch_assoc()['c'];
    $diproses = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE status='DIPROSES'")->fetch_assoc()['c'];
    $selesai  = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE status='SELESAI'")->fetch_assoc()['c'];
} else {
    $where = "WHERE wr.user_id=$user_id" . ($filter_status ? " AND wr.status='$filter_status'" : '');
    $reports = $conn->query("
        SELECT wr.*, u.nama as user_nama
        FROM waste_reports wr
        JOIN users u ON wr.user_id = u.id
        $where
        ORDER BY wr.tanggal_laporan DESC
    ");
    $total    = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$user_id")->fetch_assoc()['c'];
    $menunggu = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$user_id AND status='MENUNGGU'")->fetch_assoc()['c'];
    $diproses = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$user_id AND status='DIPROSES'")->fetch_assoc()['c'];
    $selesai  = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE user_id=$user_id AND status='SELESAI'")->fetch_assoc()['c'];
}
?>
<?php include 'includes/header.php'; ?>

<div class="d-flex">
    <?php include ($is_admin ? 'includes/sidebar_admin.php' : 'includes/sidebar_user.php'); ?>

    <div class="main-content flex-grow-1">
        <div class="topbar">
            <h1 class="topbar-title"><?= $is_admin ? 'Laporan' : 'Laporan Saya' ?> <span><?= $is_admin ? 'Warga' : '' ?></span></h1>
            <div class="topbar-right">
                <?php if (!$is_admin): ?>
                <button class="btn-eco btn-eco-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle-fill"></i> Buat Laporan
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-container">
            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb-eco"><a href="<?= $is_admin ? 'dashboard_admin.php' : 'dashboard_user.php' ?>"><i class="bi bi-house-fill"></i> Dashboard</a><i class="bi bi-chevron-right"></i>Laporan</div>
                    <h1><?= $is_admin ? 'Manajemen Laporan Warga' : 'Laporan Sampah Saya' ?></h1>
                    <p><?= $is_admin ? 'Tinjau dan tindaklanjuti laporan dari warga' : 'Buat dan pantau laporan sampah yang Anda kirimkan' ?></p>
                </div>
                <?php if (!$is_admin): ?>
                <button class="btn-eco" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-file-earmark-plus-fill"></i> Buat Laporan Baru
                </button>
                <?php endif; ?>
            </div>

            <?php if ($msg): ?>
            <div class="alert-eco <?= $msg_type ?> mb-4">
                <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
            <?php endif; ?>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card green">
                        <div class="stat-icon green"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="stat-info"><div class="stat-value" data-count="<?= $total ?>"><?= $total ?></div><div class="stat-label">Total Laporan</div></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card orange">
                        <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-info"><div class="stat-value" data-count="<?= $menunggu ?>"><?= $menunggu ?></div><div class="stat-label">Menunggu</div></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card blue">
                        <div class="stat-icon blue"><i class="bi bi-gear-fill"></i></div>
                        <div class="stat-info"><div class="stat-value" data-count="<?= $diproses ?>"><?= $diproses ?></div><div class="stat-label">Diproses</div></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card green">
                        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-info"><div class="stat-value" data-count="<?= $selesai ?>"><?= $selesai ?></div><div class="stat-label">Selesai</div></div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="d-flex gap-2 mb-4 flex-wrap">
                <?php foreach (['' => 'Semua', 'MENUNGGU' => 'Menunggu', 'DIPROSES' => 'Diproses', 'SELESAI' => 'Selesai'] as $key => $label): ?>
                <a href="?status=<?= $key ?>" class="<?= $filter_status === $key ? 'btn-eco btn-eco-sm' : 'btn-outline-eco' ?>" style="padding:7px 16px;font-size:13px;"><?= $label ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Reports Table/Cards -->
            <?php if ($reports && $reports->num_rows > 0): ?>

            <!-- Card Grid View -->
            <div class="row g-3 mb-4">
                <?php while ($r = $reports->fetch_assoc()):
                    $st = strtolower($r['status']);
                    $badge = ['menunggu'=>'badge-menunggu','diproses'=>'badge-diproses','selesai'=>'badge-selesai'][$st] ?? 'badge-menunggu';
                    $kat = strtolower($r['jenis_sampah']);
                    $icon = ['organik'=>'bi-tree-fill','anorganik'=>'bi-recycle','b3'=>'bi-radioactive'];
                    $icon_class = $icon[$kat] ?? 'bi-trash3-fill';
                ?>
                <div class="col-lg-6">
                    <div class="card-modern p-0">
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:44px;height:44px;background:linear-gradient(135deg,#16a34a,#22c55e);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;flex-shrink:0;">
                                        <i class="bi <?= $icon_class ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-700" style="font-size:14px;"><?= htmlspecialchars($r['jenis_sampah']) ?></div>
                                        <?php if ($is_admin): ?>
                                        <div style="font-size:12px;color:#64748b;"><i class="bi bi-person me-1"></i><?= htmlspecialchars($r['user_nama']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge-status <?= $badge ?>"><?= $r['status'] ?></span>
                            </div>

                            <div class="mb-2" style="font-size:13px;color:#374151;">
                                <i class="bi bi-geo-alt-fill text-success me-1"></i>
                                <?= htmlspecialchars($r['lokasi']) ?>
                            </div>

                            <p style="font-size:13px;color:#64748b;margin-bottom:12px;line-height:1.6;">
                                <?= nl2br(htmlspecialchars($r['deskripsi'])) ?>
                            </p>

                            <?php if ($r['foto']): ?>
                            <div class="mb-3">
                                <img src="/TUBES WEB RISWAN/uploads/<?= htmlspecialchars($r['foto']) ?>"
                                     alt="Foto Laporan"
                                     class="w-100 rounded-3"
                                     style="max-height:180px;object-fit:cover;cursor:pointer;"
                                     onclick="window.open(this.src,'_blank')">
                            </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y H:i', strtotime($r['tanggal_laporan'])) ?></small>
                                <div class="d-flex gap-2">
                                    <?php if ($is_admin): ?>
                                    <!-- Update Status -->
                                    <form method="POST" class="d-flex gap-1 align-items-center">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <select name="status" class="form-select" style="font-size:12px;padding:4px 8px;height:auto;width:auto;"
                                                onchange="this.form.submit()">
                                            <?php foreach (['MENUNGGU','DIPROSES','SELESAI'] as $s): ?>
                                            <option value="<?=$s?>" <?= $r['status']==$s?'selected':'' ?>><?=$s?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                    <form method="POST" id="del-report-<?= $r['id'] ?>" style="display:none;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    </form>
                                    <button class="action-btn delete" onclick="confirmDelete('del-report-<?= $r['id'] ?>', 'laporan ini')">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <?php else: ?>
            <div class="table-card">
                <div class="empty-state">
                    <i class="bi bi-file-earmark-x"></i>
                    <h5>Belum ada laporan</h5>
                    <p><?= $is_admin ? 'Belum ada warga yang mengirim laporan' : 'Anda belum pernah membuat laporan' ?></p>
                    <?php if (!$is_admin): ?>
                    <button class="btn-eco" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-file-earmark-plus-fill"></i> Buat Laporan Pertama
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php if (!$is_admin): ?>
<!-- Add Report Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-plus-fill me-2"></i>Buat Laporan Sampah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateReportForm()">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="lokasi" class="form-label">Lokasi Sampah <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="bi bi-geo-alt-fill input-icon"></i>
                                <input type="text" class="form-control" id="lokasi" name="lokasi" placeholder="Alamat lengkap lokasi sampah">
                            </div>
                        </div>
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <label class="form-label">Foto Kondisi Sampah</label>
                            <div class="img-preview-wrap" id="foto-preview" onclick="document.getElementById('foto').click()" style="height:100px;">
                                <div class="img-preview-placeholder">
                                    <i class="bi bi-camera-fill" style="font-size:20px;color:var(--primary);"></i>
                                    <p style="font-size:12px;margin:4px 0 0;color:#64748b;">Klik untuk upload</p>
                                </div>
                            </div>
                            <input type="file" id="foto" name="foto" accept="image/*" style="display:none;">
                        </div>
                        <div class="col-12">
                            <label for="deskripsi" class="form-label">Deskripsi Kondisi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"
                                      placeholder="Jelaskan kondisi sampah, banyaknya, dan informasi penting lainnya..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-eco"><i class="bi bi-send-fill me-1"></i>Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
