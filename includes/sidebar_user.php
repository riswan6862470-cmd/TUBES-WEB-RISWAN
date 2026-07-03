<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar User -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-recycle"></i>
        </div>
        <div class="brand-text">
            <span class="brand-title">SmartWaste</span>
            <span class="brand-sub">Portal Warga</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar user-avatar-green">
            <i class="bi bi-person-circle"></i>
        </div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($_SESSION['user_nama'] ?? 'Warga') ?></span>
            <span class="user-role"><i class="bi bi-house-fill me-1"></i>Warga</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">MENU UTAMA</div>

        <a href="/TUBES WEB RISWAN/dashboard_user.php"
           class="nav-item <?= $current_page == 'dashboard_user.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-label">LAYANAN</div>

        <a href="/TUBES WEB RISWAN/reports.php"
           class="nav-item <?= $current_page == 'reports.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-plus-fill"></i>
            <span>Buat Laporan</span>
        </a>

        <a href="/TUBES WEB RISWAN/pickup.php"
           class="nav-item <?= $current_page == 'pickup.php' ? 'active' : '' ?>">
            <i class="bi bi-truck-front-fill"></i>
            <span>Jadwal Penjemputan</span>
        </a>

        <a href="/TUBES WEB RISWAN/bins.php"
           class="nav-item <?= $current_page == 'bins.php' ? 'active' : '' ?>">
            <i class="bi bi-box-fill"></i>
            <span>Status Bin</span>
        </a>

        <div class="nav-section-label">AKUN</div>

        <a href="/TUBES WEB RISWAN/profile.php"
           class="nav-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person-gear-fill"></i>
            <span>Profil Saya</span>
        </a>

        <a href="/TUBES WEB RISWAN/logout.php" class="nav-item logout-item">
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
