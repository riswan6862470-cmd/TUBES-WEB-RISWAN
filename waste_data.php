<?php
session_start();
require_once 'config/db.php';

// Must be logged in (admin or user)
if (!isAdmin() && !isUser()) redirect('login.php');

$page_title = 'Data Sampah';
$is_admin = isAdmin();

$msg = '';
$msg_type = '';
$edit_data = null;

// Handle CRUD (admin only)
if ($is_admin) {
    // ADD
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        $nama_sampah        = sanitize($conn, $_POST['nama_sampah'] ?? '');
        $kategori           = sanitize($conn, $_POST['kategori'] ?? '');
        $berat              = sanitize($conn, $_POST['berat'] ?? '');
        $lokasi_pengumpulan = sanitize($conn, $_POST['lokasi_pengumpulan'] ?? '');
        $tanggal_input      = sanitize($conn, $_POST['tanggal_input'] ?? '');

        if (empty($nama_sampah) || empty($kategori) || empty($berat) || empty($lokasi_pengumpulan) || empty($tanggal_input)) {
            $msg = 'Semua field wajib diisi!';
            $msg_type = 'error';
        } else {
            $sql = "INSERT INTO waste_data (nama_sampah, kategori, berat, lokasi_pengumpulan, tanggal_input)
                    VALUES ('$nama_sampah', '$kategori', '$berat', '$lokasi_pengumpulan', '$tanggal_input')";
            if ($conn->query($sql)) {
                $msg = 'Data sampah berhasil ditambahkan!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal menambahkan data: ' . $conn->error;
                $msg_type = 'error';
            }
        }
    }

    // EDIT
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id                 = intval($_POST['id'] ?? 0);
        $nama_sampah        = sanitize($conn, $_POST['nama_sampah'] ?? '');
        $kategori           = sanitize($conn, $_POST['kategori'] ?? '');
        $berat              = sanitize($conn, $_POST['berat'] ?? '');
        $lokasi_pengumpulan = sanitize($conn, $_POST['lokasi_pengumpulan'] ?? '');
        $tanggal_input      = sanitize($conn, $_POST['tanggal_input'] ?? '');

        if ($id <= 0 || empty($nama_sampah) || empty($kategori) || empty($berat) || empty($lokasi_pengumpulan) || empty($tanggal_input)) {
            $msg = 'Data tidak valid!';
            $msg_type = 'error';
        } else {
            $sql = "UPDATE waste_data SET
                        nama_sampah='$nama_sampah',
                        kategori='$kategori',
                        berat='$berat',
                        lokasi_pengumpulan='$lokasi_pengumpulan',
                        tanggal_input='$tanggal_input'
                    WHERE id=$id";
            if ($conn->query($sql)) {
                $msg = 'Data sampah berhasil diperbarui!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal memperbarui data: ' . $conn->error;
                $msg_type = 'error';
            }
        }
    }

    // DELETE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            if ($conn->query("DELETE FROM waste_data WHERE id=$id")) {
                $msg = 'Data sampah berhasil dihapus!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal menghapus data!';
                $msg_type = 'error';
            }
        }
    }
}

// Get edit data
if ($is_admin && isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM waste_data WHERE id=$eid LIMIT 1");
    if ($res && $res->num_rows > 0) $edit_data = $res->fetch_assoc();
}

// Fetch all data
$search = sanitize($conn, $_GET['search'] ?? '');
$where = $search ? "WHERE nama_sampah LIKE '%$search%' OR kategori LIKE '%$search%' OR lokasi_pengumpulan LIKE '%$search%'" : '';
$waste_list = $conn->query("SELECT * FROM waste_data $where ORDER BY tanggal_input DESC, id DESC");

// Category summary
$summary = [];
$res_sum = $conn->query("SELECT kategori, COUNT(*) as jumlah, SUM(berat) as total_berat FROM waste_data GROUP BY kategori");
if ($res_sum) while ($s = $res_sum->fetch_assoc()) $summary[$s['kategori']] = $s;
?>
<?php include 'includes/header.php'; ?>

<div class="d-flex">
    <?php include ($is_admin ? 'includes/sidebar_admin.php' : 'includes/sidebar_user.php'); ?>

    <div class="main-content flex-grow-1">
        <!-- Topbar -->
        <div class="topbar">
            <h1 class="topbar-title">Data <span>Sampah</span></h1>
            <div class="topbar-right">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <form method="GET" style="display:contents;">
                        <input type="text" name="search" placeholder="Cari sampah..." value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()">
                    </form>
                </div>
            </div>
        </div>

        <div class="page-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb-eco"><a href="<?= $is_admin ? 'dashboard_admin.php' : 'dashboard_user.php' ?>"><i class="bi bi-house-fill"></i> Dashboard</a><i class="bi bi-chevron-right"></i>Data Sampah</div>
                    <h1>Manajemen Data Sampah</h1>
                    <p>Kelola data sampah yang terkumpul di setiap lokasi</p>
                </div>
                <?php if ($is_admin): ?>
                <button class="btn-eco" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Data
                </button>
                <?php endif; ?>
            </div>

            <?php if ($msg): ?>
            <div class="alert-eco <?= $msg_type ?> mb-4">
                <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <?php
                $cats = ['Organik' => ['color'=>'green','icon'=>'bi-tree-fill','badge'=>'badge-organik'],
                         'Anorganik' => ['color'=>'blue','icon'=>'bi-recycle','badge'=>'badge-anorganik'],
                         'B3' => ['color'=>'orange','icon'=>'bi-radioactive','badge'=>'badge-b3']];
                foreach ($cats as $cat => $meta):
                    $s = $summary[$cat] ?? ['jumlah'=>0,'total_berat'=>0];
                ?>
                <div class="col-md-4">
                    <div class="stat-card <?= $meta['color'] ?>">
                        <div class="stat-icon <?= $meta['color'] ?>"><i class="bi <?= $meta['icon'] ?>"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $s['jumlah'] ?>"><?= $s['jumlah'] ?></div>
                            <div class="stat-label">Sampah <?= $cat ?></div>
                            <div class="stat-sub"><?= number_format($s['total_berat'] ?? 0, 1) ?> kg total</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Data Table -->
            <div class="table-card">
                <div class="table-card-header">
                    <h5 class="table-card-title"><i class="bi bi-trash3-fill"></i> Daftar Data Sampah</h5>
                    <span class="text-muted" style="font-size:13px;"><?= $waste_list ? $waste_list->num_rows : 0 ?> data</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="wasteTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Sampah</th>
                                <th>Kategori</th>
                                <th>Berat (kg)</th>
                                <th>Lokasi</th>
                                <th>Tanggal</th>
                                <?php if ($is_admin): ?><th>Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($waste_list && $waste_list->num_rows > 0):
                                $no = 1;
                                while ($w = $waste_list->fetch_assoc()):
                                    $kat = strtolower($w['kategori']);
                                    $badge_kat = ['organik'=>'badge-organik','anorganik'=>'badge-anorganik','b3'=>'badge-b3'][$kat] ?? '';
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td class="fw-600"><?= htmlspecialchars($w['nama_sampah']) ?></td>
                                <td><span class="badge-status <?= $badge_kat ?>"><?= $w['kategori'] ?></span></td>
                                <td><?= number_format($w['berat'], 2) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($w['lokasi_pengumpulan']) ?></td>
                                <td><?= date('d M Y', strtotime($w['tanggal_input'])) ?></td>
                                <?php if ($is_admin): ?>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="?edit=<?= $w['id'] ?>" class="action-btn edit" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                        <form method="POST" id="del-<?= $w['id'] ?>" style="display:none;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $w['id'] ?>">
                                        </form>
                                        <button class="action-btn delete" title="Hapus"
                                            onclick="confirmDelete('del-<?= $w['id'] ?>', '<?= htmlspecialchars($w['nama_sampah'], ENT_QUOTES) ?>')">
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
                                        <i class="bi bi-inbox"></i>
                                        <h5>Belum ada data sampah</h5>
                                        <p>Data sampah akan muncul setelah ditambahkan</p>
                                        <?php if ($is_admin): ?>
                                        <button class="btn-eco" data-bs-toggle="modal" data-bs-target="#addModal">
                                            <i class="bi bi-plus-circle-fill"></i> Tambah Data
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

        </div><!-- .page-container -->
    </div><!-- .main-content -->
</div>

<?php if ($is_admin): ?>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Data Sampah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" onsubmit="return validateWasteForm()">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nama_sampah" class="form-label">Nama Sampah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_sampah" name="nama_sampah" placeholder="mis. Sisa Makanan">
                        </div>
                        <div class="col-md-6">
                            <label for="kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="kategori" name="kategori">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Organik">Organik</option>
                                <option value="Anorganik">Anorganik</option>
                                <option value="B3">B3</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="berat" class="form-label">Berat (kg) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="berat" name="berat" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_input" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_input" name="tanggal_input" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label for="lokasi_pengumpulan" class="form-label">Lokasi Pengumpulan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lokasi_pengumpulan" name="lokasi_pengumpulan" placeholder="mis. TPS Blok A">
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
                <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit Data Sampah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Sampah</label>
                            <input type="text" class="form-control" name="nama_sampah" value="<?= htmlspecialchars($edit_data['nama_sampah']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="kategori">
                                <?php foreach (['Organik','Anorganik','B3'] as $k): ?>
                                <option value="<?=$k?>" <?= $edit_data['kategori']==$k?'selected':'' ?>><?=$k?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Berat (kg)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="berat" value="<?= $edit_data['berat'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" name="tanggal_input" value="<?= $edit_data['tanggal_input'] ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Lokasi Pengumpulan</label>
                            <input type="text" class="form-control" name="lokasi_pengumpulan" value="<?= htmlspecialchars($edit_data['lokasi_pengumpulan']) ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="waste_data.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn-eco"><i class="bi bi-check-circle-fill me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
});
</script>
<?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
<script>
filterTable('search', 'wasteTable');
</script>
