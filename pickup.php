<?php
session_start();
require_once 'config/db.php';
if (!isAdmin() && !isUser()) redirect('login.php');

$page_title = 'Jadwal Penjemputan';
$is_admin = isAdmin();
$msg = '';
$msg_type = '';
$edit_data = null;

if ($is_admin) {
    // ADD
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
        $lokasi        = sanitize($conn, $_POST['lokasi'] ?? '');
        $tanggal_jemput = sanitize($conn, $_POST['tanggal_jemput'] ?? '');
        $jam_jemput    = sanitize($conn, $_POST['jam_jemput'] ?? '');
        $status        = sanitize($conn, $_POST['status'] ?? 'MENUNGGU');
        $catatan       = sanitize($conn, $_POST['catatan'] ?? '');

        if (empty($lokasi) || empty($tanggal_jemput) || empty($jam_jemput)) {
            $msg = 'Field lokasi, tanggal, dan jam wajib diisi!';
            $msg_type = 'error';
        } else {
            $catatan_val = $catatan ? "'$catatan'" : "NULL";
            $sql = "INSERT INTO pickup_schedule (lokasi, tanggal_jemput, jam_jemput, status, catatan)
                    VALUES ('$lokasi', '$tanggal_jemput', '$jam_jemput', '$status', $catatan_val)";
            if ($conn->query($sql)) {
                $msg = 'Jadwal penjemputan berhasil ditambahkan!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal menambahkan jadwal: ' . $conn->error;
                $msg_type = 'error';
            }
        }
    }

    // EDIT
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'edit') {
        $id            = intval($_POST['id'] ?? 0);
        $lokasi        = sanitize($conn, $_POST['lokasi'] ?? '');
        $tanggal_jemput = sanitize($conn, $_POST['tanggal_jemput'] ?? '');
        $jam_jemput    = sanitize($conn, $_POST['jam_jemput'] ?? '');
        $status        = sanitize($conn, $_POST['status'] ?? 'MENUNGGU');
        $catatan       = sanitize($conn, $_POST['catatan'] ?? '');

        if ($id <= 0 || empty($lokasi)) {
            $msg = 'Data tidak valid!';
            $msg_type = 'error';
        } else {
            $catatan_val = $catatan ? "'$catatan'" : "NULL";
            $sql = "UPDATE pickup_schedule SET
                        lokasi='$lokasi', tanggal_jemput='$tanggal_jemput',
                        jam_jemput='$jam_jemput', status='$status', catatan=$catatan_val
                    WHERE id=$id";
            if ($conn->query($sql)) {
                $msg = 'Jadwal berhasil diperbarui!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal memperbarui jadwal: ' . $conn->error;
                $msg_type = 'error';
            }
        }
    }

    // DELETE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0 && $conn->query("DELETE FROM pickup_schedule WHERE id=$id")) {
            $msg = 'Jadwal berhasil dihapus!';
            $msg_type = 'success';
        } else {
            $msg = 'Gagal menghapus jadwal!';
            $msg_type = 'error';
        }
    }
}

// Get edit data
if ($is_admin && isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM pickup_schedule WHERE id=$eid LIMIT 1");
    if ($res && $res->num_rows > 0) $edit_data = $res->fetch_assoc();
}

// Stats
$today = date('Y-m-d');
$total     = $conn->query("SELECT COUNT(*) as c FROM pickup_schedule")->fetch_assoc()['c'];
$menunggu  = $conn->query("SELECT COUNT(*) as c FROM pickup_schedule WHERE status='MENUNGGU'")->fetch_assoc()['c'];
$dijemput  = $conn->query("SELECT COUNT(*) as c FROM pickup_schedule WHERE status='DIJEMPUT'")->fetch_assoc()['c'];
$selesai   = $conn->query("SELECT COUNT(*) as c FROM pickup_schedule WHERE status='SELESAI'")->fetch_assoc()['c'];
$today_cnt = $conn->query("SELECT COUNT(*) as c FROM pickup_schedule WHERE tanggal_jemput='$today'")->fetch_assoc()['c'];

// Fetch pickups
$filter = isset($_GET['filter']) ? sanitize($conn, $_GET['filter']) : '';
$where = $filter ? "WHERE status='$filter'" : '';
$pickups = $conn->query("SELECT * FROM pickup_schedule $where ORDER BY tanggal_jemput ASC, jam_jemput ASC");
?>
<?php include 'includes/header.php'; ?>

<div class="d-flex">
    <?php include ($is_admin ? 'includes/sidebar_admin.php' : 'includes/sidebar_user.php'); ?>

    <div class="main-content flex-grow-1">
        <div class="topbar">
            <h1 class="topbar-title">Jadwal <span>Penjemputan</span></h1>
            <div class="topbar-right">
                <?php if ($is_admin): ?>
                <button class="btn-eco btn-eco-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Jadwal
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb-eco"><a href="<?= $is_admin ? 'dashboard_admin.php' : 'dashboard_user.php' ?>"><i class="bi bi-house-fill"></i> Dashboard</a><i class="bi bi-chevron-right"></i>Jadwal Penjemputan</div>
                    <h1>Jadwal Penjemputan Sampah</h1>
                    <p>Kelola dan pantau jadwal pengangkutan sampah di setiap wilayah</p>
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
                        <div class="stat-icon green"><i class="bi bi-calendar2-check-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $total ?>"><?= $total ?></div>
                            <div class="stat-label">Total Jadwal</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card orange">
                        <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $menunggu ?>"><?= $menunggu ?></div>
                            <div class="stat-label">Menunggu</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="border-color:#e9d5ff;">
                        <div class="stat-icon purple"><i class="bi bi-truck-front-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $dijemput ?>"><?= $dijemput ?></div>
                            <div class="stat-label">Sedang Dijemput</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card green">
                        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $selesai ?>"><?= $selesai ?></div>
                            <div class="stat-label">Selesai</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="d-flex gap-2 mb-4 flex-wrap">
                <?php
                $filters = ['' => 'Semua', 'MENUNGGU' => 'Menunggu', 'DIJEMPUT' => 'Dijemput', 'SELESAI' => 'Selesai'];
                foreach ($filters as $key => $label):
                    $active = $filter === $key;
                ?>
                <a href="?filter=<?= $key ?>"
                   class="<?= $active ? 'btn-eco btn-eco-sm' : 'btn-outline-eco' ?>"
                   style="padding:7px 16px;font-size:13px;">
                   <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Schedule Table -->
            <div class="table-card">
                <div class="table-card-header">
                    <h5 class="table-card-title"><i class="bi bi-truck-front-fill"></i> Daftar Jadwal Penjemputan</h5>
                    <span class="text-muted" style="font-size:13px;"><?= $pickups ? $pickups->num_rows : 0 ?> jadwal</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lokasi</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <?php if ($is_admin): ?><th>Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pickups && $pickups->num_rows > 0):
                                $no = 1;
                                while ($p = $pickups->fetch_assoc()):
                                    $st = strtolower($p['status']);
                                    $badge = ['menunggu'=>'badge-menunggu','dijemput'=>'badge-dijemput','selesai'=>'badge-selesai'][$st] ?? 'badge-menunggu';
                                    $is_today = $p['tanggal_jemput'] == $today;
                                    $is_past = $p['tanggal_jemput'] < $today;
                            ?>
                            <tr style="<?= $is_today ? 'background:#f0fdf4;' : '' ?>">
                                <td class="text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-geo-alt-fill text-success"></i>
                                        <span class="fw-600"><?= htmlspecialchars($p['lokasi']) ?></span>
                                        <?php if ($is_today): ?>
                                        <span class="badge bg-success text-white" style="font-size:10px;padding:3px 8px;">HARI INI</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= date('d M Y', strtotime($p['tanggal_jemput'])) ?></td>
                                <td><i class="bi bi-clock me-1 text-muted"></i><?= date('H:i', strtotime($p['jam_jemput'])) ?> WIB</td>
                                <td><span class="badge-status <?= $badge ?>"><?= $p['status'] ?></span></td>
                                <td class="text-muted" style="font-size:13px;"><?= $p['catatan'] ? htmlspecialchars($p['catatan']) : '-' ?></td>
                                <?php if ($is_admin): ?>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="?edit=<?= $p['id'] ?>" class="action-btn edit" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                        <form method="POST" id="del-pickup-<?= $p['id'] ?>" style="display:none;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        </form>
                                        <button class="action-btn delete" title="Hapus"
                                            onclick="confirmDelete('del-pickup-<?= $p['id'] ?>', '<?= htmlspecialchars($p['lokasi'], ENT_QUOTES) ?>')">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile;
                            else: ?>
                            <tr>
                                <td colspan="<?= $is_admin ? 7 : 6 ?>" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="bi bi-truck-front"></i>
                                        <h5>Belum ada jadwal penjemputan</h5>
                                        <?php if ($is_admin): ?>
                                        <button class="btn-eco" data-bs-toggle="modal" data-bs-target="#addModal">
                                            <i class="bi bi-plus-circle-fill"></i> Tambah Jadwal
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Jadwal Penjemputan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="lokasi" class="form-label">Lokasi Penjemputan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lokasi" name="lokasi" placeholder="mis. TPS Blok A - Jl. Merdeka">
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_jemput" class="form-label">Tanggal Jemput <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_jemput" name="tanggal_jemput" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="jam_jemput" class="form-label">Jam Jemput <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="jam_jemput" name="jam_jemput" value="07:00">
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="MENUNGGU">MENUNGGU</option>
                                <option value="DIJEMPUT">DIJEMPUT</option>
                                <option value="SELESAI">SELESAI</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="catatan" class="form-label">Catatan</label>
                            <input type="text" class="form-control" id="catatan" name="catatan" placeholder="Opsional...">
                        </div>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit Jadwal Penjemputan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Lokasi</label>
                            <input type="text" class="form-control" name="lokasi" value="<?= htmlspecialchars($edit_data['lokasi']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Jemput</label>
                            <input type="date" class="form-control" name="tanggal_jemput" value="<?= $edit_data['tanggal_jemput'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Jemput</label>
                            <input type="time" class="form-control" name="jam_jemput" value="<?= $edit_data['jam_jemput'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach (['MENUNGGU','DIJEMPUT','SELESAI'] as $s): ?>
                                <option value="<?=$s?>" <?= $edit_data['status']==$s?'selected':'' ?>><?=$s?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catatan</label>
                            <input type="text" class="form-control" name="catatan" value="<?= htmlspecialchars($edit_data['catatan'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="pickup.php" class="btn btn-secondary">Batal</a>
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
