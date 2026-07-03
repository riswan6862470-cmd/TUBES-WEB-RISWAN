<?php
session_start();
require_once 'config/db.php';
if (!isAdmin() && !isUser()) redirect('login.php');

$page_title = 'Monitoring Bin';
$is_admin = isAdmin();
$msg = '';
$msg_type = '';
$edit_data = null;

if ($is_admin) {
    // ADD
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        $lokasi            = sanitize($conn, $_POST['lokasi'] ?? '');
        $kapasitas_max     = intval($_POST['kapasitas_max'] ?? 0);
        $tingkat_kepenuhan = intval($_POST['tingkat_kepenuhan'] ?? 0);
        $tingkat_kepenuhan = max(0, min(100, $tingkat_kepenuhan));
        $status = $tingkat_kepenuhan >= 80 ? 'PENUH' : 'NORMAL';

        if (empty($lokasi) || $kapasitas_max <= 0) {
            $msg = 'Lokasi dan kapasitas tidak boleh kosong!';
            $msg_type = 'error';
        } else {
            $sql = "INSERT INTO bins (lokasi, kapasitas_max, tingkat_kepenuhan, status)
                    VALUES ('$lokasi', $kapasitas_max, $tingkat_kepenuhan, '$status')";
            if ($conn->query($sql)) {
                $msg = 'Data bin berhasil ditambahkan!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal menambahkan bin: ' . $conn->error;
                $msg_type = 'error';
            }
        }
    }

    // EDIT
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id                = intval($_POST['id'] ?? 0);
        $lokasi            = sanitize($conn, $_POST['lokasi'] ?? '');
        $kapasitas_max     = intval($_POST['kapasitas_max'] ?? 0);
        $tingkat_kepenuhan = intval($_POST['tingkat_kepenuhan'] ?? 0);
        $tingkat_kepenuhan = max(0, min(100, $tingkat_kepenuhan));
        $status = $tingkat_kepenuhan >= 80 ? 'PENUH' : 'NORMAL';

        if ($id <= 0 || empty($lokasi)) {
            $msg = 'Data tidak valid!';
            $msg_type = 'error';
        } else {
            $sql = "UPDATE bins SET lokasi='$lokasi', kapasitas_max=$kapasitas_max,
                    tingkat_kepenuhan=$tingkat_kepenuhan, status='$status'
                    WHERE id=$id";
            if ($conn->query($sql)) {
                $msg = 'Data bin berhasil diperbarui!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal memperbarui bin: ' . $conn->error;
                $msg_type = 'error';
            }
        }
    }

    // DELETE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            if ($conn->query("DELETE FROM bins WHERE id=$id")) {
                $msg = 'Bin berhasil dihapus!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal menghapus bin!';
                $msg_type = 'error';
            }
        }
    }
}

// Get edit data
if ($is_admin && isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM bins WHERE id=$eid LIMIT 1");
    if ($res && $res->num_rows > 0) $edit_data = $res->fetch_assoc();
}

// Stats
$total_bins  = $conn->query("SELECT COUNT(*) as c FROM bins")->fetch_assoc()['c'];
$penuh_bins  = $conn->query("SELECT COUNT(*) as c FROM bins WHERE status='PENUH'")->fetch_assoc()['c'];
$normal_bins = $conn->query("SELECT COUNT(*) as c FROM bins WHERE status='NORMAL'")->fetch_assoc()['c'];
$avg_level   = $conn->query("SELECT AVG(tingkat_kepenuhan) as a FROM bins")->fetch_assoc()['a'];

// Fetch all bins
$bins_list = $conn->query("SELECT * FROM bins ORDER BY tingkat_kepenuhan DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="d-flex">
    <?php include ($is_admin ? 'includes/sidebar_admin.php' : 'includes/sidebar_user.php'); ?>

    <div class="main-content flex-grow-1">
        <div class="topbar">
            <h1 class="topbar-title">Monitoring <span>Bin</span></h1>
            <div class="topbar-right">
                <?php if ($is_admin): ?>
                <button class="btn-eco btn-eco-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Bin
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb-eco"><a href="<?= $is_admin ? 'dashboard_admin.php' : 'dashboard_user.php' ?>"><i class="bi bi-house-fill"></i> Dashboard</a><i class="bi bi-chevron-right"></i>Monitoring Bin</div>
                    <h1>Monitoring Bin Sampah</h1>
                    <p>Pantau tingkat kepenuhan setiap bin secara real-time</p>
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
                <div class="col-md-3">
                    <div class="stat-card green">
                        <div class="stat-icon green"><i class="bi bi-box-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $total_bins ?>"><?= $total_bins ?></div>
                            <div class="stat-label">Total Bin</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card green">
                        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $normal_bins ?>"><?= $normal_bins ?></div>
                            <div class="stat-label">Bin Normal</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card red">
                        <div class="stat-icon red"><i class="bi bi-exclamation-circle-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $penuh_bins ?>"><?= $penuh_bins ?></div>
                            <div class="stat-label">Bin Penuh</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card orange">
                        <div class="stat-icon orange"><i class="bi bi-percent"></i></div>
                        <div class="stat-info">
                            <div class="stat-value"><?= number_format($avg_level ?? 0, 0) ?>%</div>
                            <div class="stat-label">Rata-rata Penuh</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bin Cards Grid -->
            <div class="row g-3 mb-4">
                <?php if ($bins_list && $bins_list->num_rows > 0):
                    while ($bin = $bins_list->fetch_assoc()):
                        $pct = min(100, intval($bin['tingkat_kepenuhan']));
                        $is_full = $pct >= 80;
                        $status_class = $is_full ? 'penuh' : 'normal';
                        $updated = isset($bin['updated_at']) ? date('d M Y H:i', strtotime($bin['updated_at'])) : '-';
                ?>
                <div class="col-xl-3 col-md-4 col-sm-6">
                    <div class="bin-card <?= $status_class ?>">
                        <!-- Status Icon -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="bin-card-icon <?= $status_class ?>">
                                <i class="bi bi-trash<?= $is_full ? '3-fill' : '3' ?>"></i>
                            </div>
                            <span class="badge-status <?= $is_full ? 'badge-penuh' : 'badge-normal' ?>">
                                <?= $bin['status'] ?>
                            </span>
                        </div>

                        <!-- Location -->
                        <div class="fw-700 mb-1" style="font-size:14px;color:#0f172a;">
                            <?= htmlspecialchars($bin['lokasi']) ?>
                        </div>
                        <div class="text-muted mb-3" style="font-size:12px;">
                            <i class="bi bi-clock me-1"></i>Update: <?= $updated ?>
                        </div>

                        <!-- Progress Bar -->
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-600" style="color:<?= $is_full ? '#dc2626' : '#16a34a' ?>;">
                                <?= $pct ?>% Penuh
                            </small>
                            <small class="text-muted"><?= $bin['kapasitas_max'] ?> L</small>
                        </div>
                        <div class="bin-progress mb-2">
                            <div class="bin-progress-bar <?= $status_class ?>" style="width:<?= $pct ?>%"></div>
                        </div>

                        <?php if ($is_admin): ?>
                        <div class="d-flex gap-2 mt-3">
                            <a href="?edit=<?= $bin['id'] ?>" class="btn-outline-eco flex-grow-1 justify-content-center" style="padding:7px;font-size:12px;">
                                <i class="bi bi-pencil-fill"></i> Edit
                            </a>
                            <form method="POST" id="del-bin-<?= $bin['id'] ?>" style="display:none;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $bin['id'] ?>">
                            </form>
                            <button class="action-btn delete" onclick="confirmDelete('del-bin-<?= $bin['id'] ?>', '<?= htmlspecialchars($bin['lokasi'], ENT_QUOTES) ?>')">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile;
                else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-box"></i>
                        <h5>Belum ada data bin</h5>
                        <?php if ($is_admin): ?>
                        <button class="btn-eco" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-plus-circle-fill"></i> Tambah Bin
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Table View -->
            <div class="table-card">
                <div class="table-card-header">
                    <h5 class="table-card-title"><i class="bi bi-table"></i> Tabel Data Bin</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lokasi</th>
                                <th>Kapasitas (L)</th>
                                <th>Level Kepenuhan</th>
                                <th>Status</th>
                                <th>Terakhir Update</th>
                                <?php if ($is_admin): ?><th>Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $bins_list2 = $conn->query("SELECT * FROM bins ORDER BY tingkat_kepenuhan DESC");
                            if ($bins_list2 && $bins_list2->num_rows > 0):
                                $no = 1;
                                while ($bin = $bins_list2->fetch_assoc()):
                                    $pct = min(100, intval($bin['tingkat_kepenuhan']));
                                    $is_full = $pct >= 80;
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td class="fw-600"><i class="bi bi-geo-alt-fill text-success me-1"></i><?= htmlspecialchars($bin['lokasi']) ?></td>
                                <td><?= number_format($bin['kapasitas_max']) ?> L</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bin-progress" style="width:80px;">
                                            <div class="bin-progress-bar <?= $is_full ? 'penuh' : 'normal' ?>" style="width:<?= $pct ?>%"></div>
                                        </div>
                                        <span style="font-size:13px;font-weight:600;color:<?= $is_full ? '#dc2626' : '#16a34a' ?>;"><?= $pct ?>%</span>
                                    </div>
                                </td>
                                <td><span class="badge-status <?= $is_full ? 'badge-penuh' : 'badge-normal' ?>"><?= $bin['status'] ?></span></td>
                                <td class="text-muted" style="font-size:13px;"><?= isset($bin['updated_at']) ? date('d/m/Y H:i', strtotime($bin['updated_at'])) : '-' ?></td>
                                <?php if ($is_admin): ?>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="?edit=<?= $bin['id'] ?>" class="action-btn edit"><i class="bi bi-pencil-fill"></i></a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile;
                            else: ?>
                            <tr><td colspan="<?= $is_admin ? 7 : 6 ?>" class="text-center py-4 text-muted">Belum ada data bin</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php if ($is_admin): ?>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Bin Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" onsubmit="return validateBinForm()">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="lokasi" class="form-label">Lokasi Bin <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="lokasi" name="lokasi" placeholder="mis. TPS Blok A - Jl. Merdeka">
                    </div>
                    <div class="mb-3">
                        <label for="kapasitas_max" class="form-label">Kapasitas Maksimal (Liter) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="kapasitas_max" name="kapasitas_max" min="1" placeholder="100">
                    </div>
                    <div class="mb-3">
                        <label for="tingkat_kepenuhan" class="form-label">Tingkat Kepenuhan (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="tingkat_kepenuhan" name="tingkat_kepenuhan" min="0" max="100" placeholder="0-100">
                    </div>
                    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:13px;font-weight:500;">Status otomatis:</span>
                            <span class="badge-status badge-normal" id="status-preview"><i class="bi bi-check-circle-fill me-1"></i>NORMAL</span>
                        </div>
                        <small class="text-muted">≥80% = PENUH | &lt;80% = NORMAL</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-eco"><i class="bi bi-check-circle-fill me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<?php if ($edit_data): ?>
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit Bin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Lokasi Bin</label>
                        <input type="text" class="form-control" name="lokasi" value="<?= htmlspecialchars($edit_data['lokasi']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kapasitas Maksimal (L)</label>
                        <input type="number" class="form-control" name="kapasitas_max" min="1" value="<?= $edit_data['kapasitas_max'] ?>">
                    </div>
                    <div class="mb-3">
                        <label for="tingkat_kepenuhan" class="form-label">Tingkat Kepenuhan (%)</label>
                        <input type="number" class="form-control" id="tingkat_kepenuhan" name="tingkat_kepenuhan" min="0" max="100" value="<?= $edit_data['tingkat_kepenuhan'] ?>">
                    </div>
                    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                        Status: <span class="badge-status <?= $edit_data['tingkat_kepenuhan'] >= 80 ? 'badge-penuh' : 'badge-normal' ?>" id="status-preview">
                            <?= $edit_data['status'] ?>
                        </span>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="bins.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn-eco"><i class="bi bi-check-circle-fill me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('editModal')).show();
});
</script>
<?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
