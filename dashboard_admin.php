<?php
session_start();
require_once 'config/db.php';
if (!isAdmin()) redirect('login.php');

$page_title = 'Dashboard Admin';

// ---- Stats ----
$total_users   = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_reports = $conn->query("SELECT COUNT(*) as c FROM waste_reports")->fetch_assoc()['c'];
$total_bins    = $conn->query("SELECT COUNT(*) as c FROM bins")->fetch_assoc()['c'];
$bins_penuh    = $conn->query("SELECT COUNT(*) as c FROM bins WHERE status='PENUH'")->fetch_assoc()['c'];
$pending_reports = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE status='MENUNGGU'")->fetch_assoc()['c'];

// Waste by category
$cat_organik    = $conn->query("SELECT COALESCE(SUM(berat),0) as t FROM waste_data WHERE kategori='Organik'")->fetch_assoc()['t'];
$cat_anorganik  = $conn->query("SELECT COALESCE(SUM(berat),0) as t FROM waste_data WHERE kategori='Anorganik'")->fetch_assoc()['t'];
$cat_b3         = $conn->query("SELECT COALESCE(SUM(berat),0) as t FROM waste_data WHERE kategori='B3'")->fetch_assoc()['t'];

// Pickup today
$today = date('Y-m-d');
$today_pickups = $conn->query("SELECT COUNT(*) as c FROM pickup_schedule WHERE tanggal_jemput='$today'")->fetch_assoc()['c'];

// Latest reports
$latest_reports = $conn->query("
    SELECT wr.*, u.nama as user_nama
    FROM waste_reports wr
    JOIN users u ON wr.user_id = u.id
    ORDER BY wr.tanggal_laporan DESC
    LIMIT 5
");

// Bins status
$bins_data = $conn->query("SELECT * FROM bins ORDER BY tingkat_kepenuhan DESC LIMIT 5");

// Latest pickup
$upcoming_pickups = $conn->query("
    SELECT * FROM pickup_schedule
    WHERE tanggal_jemput >= '$today'
    ORDER BY tanggal_jemput ASC, jam_jemput ASC
    LIMIT 5
");
?>
<?php include 'includes/header.php'; ?>

<div class="d-flex">
    <?php include 'includes/sidebar_admin.php'; ?>

    <div class="main-content flex-grow-1">
        <!-- Topbar -->
        <div class="topbar">
            <h1 class="topbar-title">Dashboard <span>Admin</span></h1>
            <div class="topbar-right">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Cari sesuatu...">
                </div>
                <div class="topbar-badge">
                    <i class="bi bi-calendar2-check"></i>
                    <?= date('d M Y') ?>
                </div>
            </div>
        </div>

        <div class="page-container">
            <!-- Welcome Banner -->
            <div class="card-modern p-4 mb-4" style="background:linear-gradient(135deg,#0f172a,#1e293b); color:#fff; border:none;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1 fw-bold">Selamat Datang, <?= htmlspecialchars($_SESSION['admin_username']) ?>! 👋</h4>
                        <p class="mb-0 opacity-75" style="font-size:14px;">
                            <?= $bins_penuh > 0 ? "⚠️ Ada <strong>$bins_penuh bin</strong> yang perlu segera dikosongkan." : "✅ Semua bin dalam kondisi normal." ?>
                            <?= $pending_reports > 0 ? " Ada <strong>$pending_reports laporan</strong> menunggu tindakan." : "" ?>
                        </p>
                    </div>
                    <div style="font-size:52px; opacity:0.7;">🌿</div>
                </div>
            </div>

            <!-- Stat Cards Row 1 -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card green">
                        <div class="stat-icon green"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $total_users ?>"><?= $total_users ?></div>
                            <div class="stat-label">Total Warga</div>
                            <div class="stat-sub"><i class="bi bi-arrow-up-short"></i>Terdaftar</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card blue">
                        <div class="stat-icon blue"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $total_reports ?>"><?= $total_reports ?></div>
                            <div class="stat-label">Total Laporan</div>
                            <div class="stat-sub"><i class="bi bi-clock"></i> <?= $pending_reports ?> menunggu</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card red">
                        <div class="stat-icon red"><i class="bi bi-box-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $bins_penuh ?><span style="font-size:14px;font-weight:500;color:#94a3b8;">/<?=$total_bins?></span></div>
                            <div class="stat-label">Bin Penuh</div>
                            <div class="stat-sub" style="color:#ef4444;"><i class="bi bi-exclamation-triangle"></i> Perlu dikosongkan</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card teal">
                        <div class="stat-icon teal"><i class="bi bi-truck-front-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $today_pickups ?>"><?= $today_pickups ?></div>
                            <div class="stat-label">Penjemputan Hari Ini</div>
                            <div class="stat-sub"><i class="bi bi-calendar-check"></i> Terjadwal</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Waste Category Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card green">
                        <div class="stat-icon green" style="background:linear-gradient(135deg,#15803d,#22c55e);">
                            <i class="bi bi-tree-fill"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $cat_organik ?>"><?= number_format($cat_organik, 1) ?></div>
                            <div class="stat-label">Sampah Organik (kg)</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card blue">
                        <div class="stat-icon blue" style="background:linear-gradient(135deg,#1d4ed8,#60a5fa);">
                            <i class="bi bi-recycle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $cat_anorganik ?>"><?= number_format($cat_anorganik, 1) ?></div>
                            <div class="stat-label">Sampah Anorganik (kg)</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card orange">
                        <div class="stat-icon orange" style="background:linear-gradient(135deg,#b45309,#f59e0b);">
                            <i class="bi bi-radioactive"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" data-count="<?= $cat_b3 ?>"><?= number_format($cat_b3, 1) ?></div>
                            <div class="stat-label">Sampah B3 (kg)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tables Row -->
            <div class="row g-4 mb-4">
                <!-- Latest Reports -->
                <div class="col-lg-7">
                    <div class="table-card">
                        <div class="table-card-header">
                            <h5 class="table-card-title">
                                <i class="bi bi-file-earmark-text-fill"></i>
                                Laporan Terbaru
                            </h5>
                            <a href="reports.php" class="btn-eco btn-eco-sm">
                                Lihat Semua <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Warga</th>
                                        <th>Lokasi</th>
                                        <th>Jenis</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($latest_reports && $latest_reports->num_rows > 0):
                                        while ($r = $latest_reports->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width:30px;height:30px;background:linear-gradient(135deg,#16a34a,#22c55e);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;">
                                                    <?= strtoupper(substr($r['user_nama'], 0, 1)) ?>
                                                </div>
                                                <span class="fw-500"><?= htmlspecialchars($r['user_nama']) ?></span>
                                            </div>
                                        </td>
                                        <td class="text-muted" style="font-size:13px;"><?= htmlspecialchars(substr($r['lokasi'], 0, 25)) ?>...</td>
                                        <td><?= htmlspecialchars($r['jenis_sampah']) ?></td>
                                        <td>
                                            <?php
                                            $st = strtolower($r['status']);
                                            $badge_class = [
                                                'menunggu' => 'badge-menunggu',
                                                'diproses' => 'badge-diproses',
                                                'selesai' => 'badge-selesai'
                                            ][$st] ?? 'badge-menunggu';
                                            ?>
                                            <span class="badge-status <?= $badge_class ?>"><?= $r['status'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endwhile;
                                    else: ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada laporan</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bin Status -->
                <div class="col-lg-5">
                    <div class="table-card">
                        <div class="table-card-header">
                            <h5 class="table-card-title">
                                <i class="bi bi-box-fill"></i>
                                Status Bin
                            </h5>
                            <a href="bins.php" class="btn-eco btn-eco-sm">
                                Kelola <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <div class="p-3">
                            <?php if ($bins_data && $bins_data->num_rows > 0):
                                while ($bin = $bins_data->fetch_assoc()):
                                    $pct = min(100, intval($bin['tingkat_kepenuhan']));
                                    $is_full = $pct >= 80;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span style="font-size:13px;font-weight:600;color:#374151;">
                                        <?= htmlspecialchars(substr($bin['lokasi'], 0, 28)) ?>...
                                    </span>
                                    <span class="badge-status <?= $is_full ? 'badge-penuh' : 'badge-normal' ?>">
                                        <?= $bin['status'] ?>
                                    </span>
                                </div>
                                <div class="bin-progress">
                                    <div class="bin-progress-bar <?= $is_full ? 'penuh' : 'normal' ?>"
                                         style="width:<?= $pct ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted"><?= $pct ?>% penuh</small>
                                    <small class="text-muted"><?= $bin['kapasitas_max'] ?> L max</small>
                                </div>
                            </div>
                            <?php endwhile;
                            else: ?>
                            <p class="text-center text-muted py-3">Belum ada data bin</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Pickups -->
            <div class="table-card">
                <div class="table-card-header">
                    <h5 class="table-card-title">
                        <i class="bi bi-truck-front-fill"></i>
                        Jadwal Penjemputan Mendatang
                    </h5>
                    <a href="pickup.php" class="btn-eco btn-eco-sm">
                        Kelola Jadwal <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Lokasi</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($upcoming_pickups && $upcoming_pickups->num_rows > 0):
                                while ($p = $upcoming_pickups->fetch_assoc()):
                                    $st = strtolower($p['status']);
                                    $badge = [
                                        'menunggu' => 'badge-menunggu',
                                        'dijemput' => 'badge-dijemput',
                                        'selesai' => 'badge-selesai'
                                    ][$st] ?? 'badge-menunggu';
                            ?>
                            <tr>
                                <td class="fw-500"><?= htmlspecialchars($p['lokasi']) ?></td>
                                <td><?= date('d M Y', strtotime($p['tanggal_jemput'])) ?></td>
                                <td><i class="bi bi-clock me-1 text-muted"></i><?= date('H:i', strtotime($p['jam_jemput'])) ?></td>
                                <td><span class="badge-status <?= $badge ?>"><?= $p['status'] ?></span></td>
                                <td class="text-muted" style="font-size:13px;"><?= $p['catatan'] ? htmlspecialchars($p['catatan']) : '-' ?></td>
                            </tr>
                            <?php endwhile;
                            else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada jadwal mendatang</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- .page-container -->
    </div><!-- .main-content -->
</div>

<?php include 'includes/footer.php'; ?>
