<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$admin_prefix = $admin_prefix ?? '../';
$active_menu = $active_menu ?? '';
$user_role = $_SESSION['role'] ?? 'guest';
$user_name = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$user_initial = strtoupper(substr($user_name, 0, 1));
?>
<!-- Sidebar Element -->
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="<?= $admin_prefix; ?>Controller/dashboard.php">
            <span class="brand-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </span>
            <div class="brand-text">
                <span class="brand-title">Ruang Baca</span>
                <span class="brand-sub">Sistem Perpustakaan</span>
            </div>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Sembunyikan/Tampilkan Menu" title="Sembunyikan Menu">
            <svg class="toggle-icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
    </div>

    <div class="sidebar-nav-container">
        <!-- Main Navigation -->
        <div class="nav-section-label">Navigasi Utama</div>
        <nav class="sidebar-nav">
            <a class="nav-link <?= $active_menu === 'dashboard' ? 'active' : ''; ?>" href="<?= $admin_prefix; ?>Controller/dashboard.php" title="Dashboard">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </span>
                <span class="nav-label">Dashboard <?= $user_role === 'anggota' ? 'Saya' : ''; ?></span>
            </a>

            <a class="nav-link <?= $active_menu === 'katalog' ? 'active' : ''; ?>" href="<?= $admin_prefix; ?>View/katalog.php" title="Katalog Koleksi">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                    </svg>
                </span>
                <span class="nav-label">Katalog Koleksi</span>
            </a>

            <!-- Admin Only Section -->
            <?php if ($user_role === 'admin'): ?>
                <div class="nav-section-label">Layanan Petugas</div>
                <a class="nav-link <?= $active_menu === 'pinjam' ? 'active' : ''; ?>" href="<?= $admin_prefix; ?>Controller/pinjam.php" title="Peminjaman Buku">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 3h5v5"></path>
                            <path d="m21 3-7 7"></path>
                            <path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"></path>
                        </svg>
                    </span>
                    <span class="nav-label">Peminjaman Buku</span>
                </a>

                <a class="nav-link <?= $active_menu === 'tambah' ? 'active' : ''; ?>" href="<?= $admin_prefix; ?>View/tambah_buku.php" title="Tambah Buku">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="16"></line>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                        </svg>
                    </span>
                    <span class="nav-label">Tambah Buku</span>
                </a>

                <a class="nav-link <?= $active_menu === 'anggota' ? 'active' : ''; ?>" href="<?= $admin_prefix; ?>Controller/register.php" title="Buat Anggota">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <line x1="19" y1="8" x2="19" y2="14"></line>
                            <line x1="22" y1="11" x2="16" y2="11"></line>
                        </svg>
                    </span>
                    <span class="nav-label">Buat Anggota</span>
                </a>
            <?php endif; ?>

            <!-- Kepala & Admin Reports Section -->
            <?php if ($user_role === 'kepala' || $user_role === 'admin'): ?>
                <div class="nav-section-label">Laporan & Sirkulasi</div>
                <a class="nav-link <?= $active_menu === 'laporan' ? 'active' : ''; ?>" href="<?= $admin_prefix; ?>Controller/export_laporan.php" title="Laporan Sirkulasi">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </span>
                    <span class="nav-label">Laporan Sirkulasi</span>
                </a>
            <?php endif; ?>

            <!-- Public Portal Link -->
            <div class="nav-section-label">Akses Lain</div>
            <a class="nav-link" href="<?= $admin_prefix; ?>index.php" target="_blank" title="Katalog Publik (Buka Tab Baru)">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                </span>
                <span class="nav-label">Katalog Publik ↗</span>
            </a>
        </nav>
    </div>

    <!-- User Profile Footer -->
    <div class="sidebar-user-card">
        <div class="user-avatar-pill">
            <span class="user-avatar"><?= $user_initial; ?></span>
            <div class="user-info">
                <span class="user-name" title="<?= htmlspecialchars($user_name); ?>"><?= htmlspecialchars($user_name); ?></span>
                <span class="user-role-badge badge-<?= $user_role; ?>"><?= ucfirst($user_role); ?></span>
            </div>
        </div>
        <a class="btn-logout-icon" href="<?= $admin_prefix; ?>Controller/logout.php" title="Keluar dari sistem">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </a>
    </div>
</aside>

<!-- Mobile Overlay & Toggle -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<button class="sidebar-mobile-toggle" id="sidebarMobileToggle" type="button" aria-label="Buka Menu Sidebar" title="Buka Menu">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<script>
(function() {
    const sidebar = document.getElementById('appSidebar');
    const toggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('sidebarMobileToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    const pageBody = document.body;

    if (!sidebar) return;

    // Injeksi tombol toggle di Topbar secara otomatis jika ada .topbar-left
    const topbarLeft = document.querySelector('.topbar-left');
    let topbarBtn = document.getElementById('topbarToggleBtn');
    
    if (topbarLeft && !topbarBtn) {
        topbarBtn = document.createElement('button');
        topbarBtn.id = 'topbarToggleBtn';
        topbarBtn.type = 'button';
        topbarBtn.className = 'topbar-toggle-btn';
        topbarBtn.title = 'Buka/Tutup Sidebar';
        topbarBtn.setAttribute('aria-label', 'Buka/Tutup Sidebar');
        topbarBtn.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        `;
        topbarLeft.insertBefore(topbarBtn, topbarLeft.firstChild);
    }

    function updateToggleTooltips(isCollapsed) {
        if (toggle) {
            toggle.title = isCollapsed ? 'Tampilkan Menu Sidebar (Expand)' : 'Sembunyikan Menu Sidebar (Collapse)';
            toggle.setAttribute('aria-label', isCollapsed ? 'Tampilkan Menu Sidebar' : 'Sembunyikan Menu Sidebar');
        }
        if (topbarBtn) {
            topbarBtn.title = isCollapsed ? 'Tampilkan Menu Sidebar' : 'Sembunyikan Menu Sidebar';
        }
    }

    function toggleDesktopSidebar() {
        pageBody.classList.toggle('sidebar-collapsed');
        const isCollapsed = pageBody.classList.contains('sidebar-collapsed');
        localStorage.setItem('ruangbaca-sidebar-collapsed', isCollapsed ? '1' : '0');
        updateToggleTooltips(isCollapsed);
    }

    function toggleMobileSidebar() {
        pageBody.classList.toggle('mobile-nav-open');
    }

    if (toggle) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth <= 900) {
                toggleMobileSidebar();
            } else {
                toggleDesktopSidebar();
            }
        });
    }

    if (topbarBtn) {
        topbarBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth <= 900) {
                toggleMobileSidebar();
            } else {
                toggleDesktopSidebar();
            }
        });
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', toggleMobileSidebar);
    }

    if (backdrop) {
        backdrop.addEventListener('click', () => pageBody.classList.remove('mobile-nav-open'));
    }

    // Restore saved state saat reload
    const savedState = localStorage.getItem('ruangbaca-sidebar-collapsed');
    if (savedState === '1' && window.innerWidth > 900) {
        pageBody.classList.add('sidebar-collapsed');
        updateToggleTooltips(true);
    } else {
        updateToggleTooltips(false);
    }
})();
</script>
