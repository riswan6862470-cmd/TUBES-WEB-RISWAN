<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Admin -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-recycle"></i>
        </div>
        <div class="brand-text">
            <span class="brand-title">SmartWaste</span>
            <span class="brand-sub">Admin Panel</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">
            <i class="bi bi-person-badge-fill"></i>
        </div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
            <span class="user-role"><i class="bi bi-shield-check-fill me-1"></i>Administrator</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">MENU UTAMA</div>

        <a href="dashboard_admin.php"
           class="nav-item <?= $current_page == 'dashboard_admin.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-label">MANAJEMEN DATA</div>

        <a href="waste_data.php"
           class="nav-item <?= $current_page == 'waste_data.php' ? 'active' : '' ?>">
            <i class="bi bi-trash3-fill"></i>
            <span>Data Sampah</span>
        </a>

        <a href="bins.php"
           class="nav-item <?= $current_page == 'bins.php' ? 'active' : '' ?>">
            <i class="bi bi-box-fill"></i>
            <span>Monitoring Bin</span>
        </a>

        <a href="pickup.php"
           class="nav-item <?= $current_page == 'pickup.php' ? 'active' : '' ?>">
            <i class="bi bi-truck-front-fill"></i>
            <span>Jadwal Penjemputan</span>
        </a>

        <a href="reports.php"
           class="nav-item <?= $current_page == 'reports.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text-fill"></i>
            <span>Laporan Warga</span>
            <?php
            // Show count of pending reports
            global $conn;
            if ($conn) {
                $r = $conn->query("SELECT COUNT(*) as c FROM waste_reports WHERE status='MENUNGGU'");
                $cnt = $r ? $r->fetch_assoc()['c'] : 0;
                if ($cnt > 0): ?>
                <span class="badge-count"><?= $cnt ?></span>
            <?php endif; }?>
        </a>

        <div class="nav-section-label">AKUN</div>

        <a href="profile.php"
           class="nav-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person-gear-fill"></i>
            <span>Profil</span>
        </a>

        <a href="logout.php" class="nav-item logout-item">
            <i class="bi bi-box-arrow-left"></i>
            <span>Logout</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <small><i class="bi bi-circle-fill text-success me-1" style="font-size:8px;"></i>Sistem Aktif</small>
    </div>
</div>

<!-- Toggle Button Mobile -->
<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
