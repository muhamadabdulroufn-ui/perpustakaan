<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../Model/config.php';

$nama = $_SESSION['nama_lengkap'];
$role = $_SESSION['role'];
$id_user = (int)$_SESSION['id_user'];
$nim_npp = '';

// Ambil info user
$q_me = mysqli_query($conn, "SELECT nim_npp, created_at FROM users WHERE id_user = $id_user");
if ($q_me && $me_data = mysqli_fetch_assoc($q_me)) {
    $nim_npp = $me_data['nim_npp'] ?? '';
    $user_since = date('d M Y', strtotime($me_data['created_at'] ?? 'now'));
} else {
    $user_since = date('d M Y');
}

$total_buku = 0;
$buku_dipinjam = 0;
$total_anggota = 0;
$total_dikembalikan = 0;
$chart_harian = ['labels' => [], 'values' => []];
$chart_bulanan = ['labels' => [], 'values' => []];
$chart_tahunan = ['labels' => [], 'values' => []];

$q_total_buku = mysqli_query($conn, "SELECT COUNT(*) AS total FROM buku");
if ($q_total_buku) {
    $total_buku = (int)mysqli_fetch_assoc($q_total_buku)['total'];
}

$q_dipinjam = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'dipinjam'");
if ($q_dipinjam) {
    $buku_dipinjam = (int)mysqli_fetch_assoc($q_dipinjam)['total'];
}

$q_anggota = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'anggota'");
if ($q_anggota) {
    $total_anggota = (int)mysqli_fetch_assoc($q_anggota)['total'];
}

$q_dikembalikan = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'kembali'");
if ($q_dikembalikan) {
    $total_dikembalikan = (int)mysqli_fetch_assoc($q_dikembalikan)['total'];
}

$bulan_aktif = (int)date('m');
$tahun_aktif = (int)date('Y');
$jumlah_hari = (int)date('t');

// Data Chart Harian
$harian_query = mysqli_query($conn, "SELECT DAY(tanggal_pinjam) AS periode, COUNT(*) AS total FROM peminjaman WHERE MONTH(tanggal_pinjam) = $bulan_aktif AND YEAR(tanggal_pinjam) = $tahun_aktif GROUP BY DAY(tanggal_pinjam) ORDER BY periode");
$harian_data = [];
if ($harian_query) {
    while ($row = mysqli_fetch_assoc($harian_query)) {
        $harian_data[(int)$row['periode']] = (int)$row['total'];
    }
}
for ($hari = 1; $hari <= $jumlah_hari; $hari++) {
    $chart_harian['labels'][] = (string)$hari;
    $chart_harian['values'][] = $harian_data[$hari] ?? 0;
}

// Data Chart Bulanan
$bulanan_query = mysqli_query($conn, "SELECT MONTH(tanggal_pinjam) AS periode, COUNT(*) AS total FROM peminjaman WHERE YEAR(tanggal_pinjam) = $tahun_aktif GROUP BY MONTH(tanggal_pinjam) ORDER BY periode");
$bulanan_data = [];
if ($bulanan_query) {
    while ($row = mysqli_fetch_assoc($bulanan_query)) {
        $bulanan_data[(int)$row['periode']] = (int)$row['total'];
    }
}
$nama_bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$chart_bulanan['labels'] = $nama_bulan;
$chart_bulanan['values'] = array_map(static fn ($bulan) => $bulanan_data[$bulan] ?? 0, range(1, 12));

// Data Chart Tahunan
$tahun_mulai = $tahun_aktif - 4;
$tahunan_query = mysqli_query($conn, "SELECT YEAR(tanggal_pinjam) AS periode, COUNT(*) AS total FROM peminjaman WHERE YEAR(tanggal_pinjam) BETWEEN $tahun_mulai AND $tahun_aktif GROUP BY YEAR(tanggal_pinjam) ORDER BY periode");
$tahunan_data = [];
if ($tahunan_query) {
    while ($row = mysqli_fetch_assoc($tahunan_query)) {
        $tahunan_data[(int)$row['periode']] = (int)$row['total'];
    }
}
for ($thn = $tahun_mulai; $thn <= $tahun_aktif; $thn++) {
    $chart_tahunan['labels'][] = (string)$thn;
    $chart_tahunan['values'][] = $tahunan_data[$thn] ?? 0;
}

// KHUSUS ANGGOTA: Ambil data pinjaman aktif dan riwayat
$pinjaman_aktif_anggota = [];
$riwayat_anggota = [];
$total_pernah_pinjam = 0;

if ($role === 'anggota') {
    $q_aktif = mysqli_query($conn, "SELECT p.*, b.judul, b.penulis, b.penerbit, b.cover_path FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku WHERE p.id_user = $id_user AND p.status = 'dipinjam' ORDER BY p.tanggal_pinjam DESC");
    if ($q_aktif) {
        while ($row = mysqli_fetch_assoc($q_aktif)) {
            $pinjaman_aktif_anggota[] = $row;
        }
    }

    $q_history = mysqli_query($conn, "SELECT p.*, b.judul, b.penulis, b.penerbit FROM peminjaman p JOIN buku b ON p.id_buku = b.id_buku WHERE p.id_user = $id_user AND p.status = 'kembali' ORDER BY p.tanggal_kembali DESC LIMIT 5");
    if ($q_history) {
        while ($row = mysqli_fetch_assoc($q_history)) {
            $riwayat_anggota[] = $row;
        }
    }

    $q_count_me = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE id_user = $id_user");
    $total_pernah_pinjam = mysqli_fetch_assoc($q_count_me)['total'] ?? 0;
}

// KHUSUS ADMIN: Ambil 5 transaksi aktif yang perlu dipantau
$transaksi_aktif_admin = [];
if ($role === 'admin') {
    $q_admin_trx = mysqli_query($conn, "SELECT p.*, u.nama_lengkap, u.nim_npp, b.judul, b.id_buku FROM peminjaman p JOIN users u ON p.id_user = u.id_user JOIN buku b ON p.id_buku = b.id_buku WHERE p.status = 'dipinjam' ORDER BY p.tanggal_pinjam DESC LIMIT 6");
    if ($q_admin_trx) {
        while ($row = mysqli_fetch_assoc($q_admin_trx)) {
            $transaksi_aktif_admin[] = $row;
        }
    }
}

// KHUSUS KEPALA: Ambil Top 5 Buku Terpopuler
$top_buku = [];
if ($role === 'kepala') {
    $q_top = mysqli_query($conn, "SELECT b.id_buku, b.judul, b.penulis, b.stok, b.cover_path, COUNT(p.id_peminjaman) AS total_pinjam FROM buku b LEFT JOIN peminjaman p ON b.id_buku = p.id_buku GROUP BY b.id_buku ORDER BY total_pinjam DESC, b.judul ASC LIMIT 5");
    if ($q_top) {
        while ($row = mysqli_fetch_assoc($q_top)) {
            $top_buku[] = $row;
        }
    }
}

$notif = $_GET['pesan'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard <?= ucfirst($role); ?> - Ruang Baca</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../View/partials/admin_sidebar.css">
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
            color: #ffffff;
            border-radius: var(--radius-xl);
            padding: 32px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: 0 16px 36px -6px rgba(15, 23, 42, 0.25);
            margin-bottom: 28px;
        }
        .welcome-banner::before {
            content: '';
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.2) 0%, transparent 70%);
            right: -50px;
            top: -50px;
        }
        .welcome-info {
            position: relative;
            z-index: 2;
            max-width: 600px;
        }
        .welcome-info h1 {
            font-size: clamp(1.6rem, 3.5vw, 2.3rem);
            color: #ffffff;
            margin-bottom: 8px;
        }
        .welcome-info p {
            color: #cbd5e1;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .welcome-badge {
            position: relative;
            z-index: 2;
            text-align: right;
        }

        /* Metric Cards Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        .metric-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 22px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-normal);
        }
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        .metric-content {
            display: flex;
            flex-direction: column;
        }
        .metric-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .metric-val {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-top: 4px;
        }
        .metric-icon-wrap {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-md);
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .icon-blue { background: #eef2ff; color: #4f46e5; }
        .icon-cyan { background: #ecfeff; color: #0891b2; }
        .icon-amber { background: #fffbeb; color: #d97706; }
        .icon-emerald { background: #ecfdf5; color: #059669; }

        /* Quick Action Bar for Admin */
        .quick-actions-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        /* Chart Toolbar */
        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .chart-period-tabs {
            display: flex;
            background: var(--bg-page);
            padding: 4px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            gap: 4px;
        }
        .chart-tab-btn {
            border: none;
            background: transparent;
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition-fast);
        }
        .chart-tab-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        @media (max-width: 768px) {
            .welcome-banner { flex-direction: column; align-items: flex-start; gap: 16px; padding: 24px; }
            .welcome-badge { text-align: left; }
            .metrics-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .metrics-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="has-sidebar dashboard-page">

    <!-- Sidebar Navigation -->
    <?php 
        $active_menu = 'dashboard'; 
        $admin_prefix = '../'; 
        include __DIR__ . '/../View/partials/admin_sidebar.php'; 
    ?>

    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <span class="badge <?= $role === 'admin' ? 'badge-primary' : ($role === 'kepala' ? 'badge-success' : 'badge-neutral'); ?>">
                    Role: <?= ucfirst($role); ?>
                </span>
                <span class="topbar-title">Dashboard Ruang Baca</span>
            </div>
            <div class="topbar-actions">
                <label class="dashboard-search" aria-label="Cari di dashboard">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-4-4"></path>
                    </svg>
                    <input id="dashboardSearch" type="search" placeholder="Cari sesuatu..." autocomplete="off">
                </label>
                <span class="text-sm text-muted" style="display: flex; align-items: center; gap: 6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?= date('d M Y'); ?>
                </span>
                <a href="logout.php" class="btn btn-secondary btn-sm" style="color: var(--danger) !important;">Keluar</a>
            </div>
        </header>

        <!-- Main Dashboard View -->
        <main class="page-content">

            <?php if ($notif): ?>
                <div class="alert alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span><?= htmlspecialchars($notif); ?></span>
                </div>
            <?php endif; ?>

            <!-- Welcome Greeting Banner -->
            <div class="welcome-banner">
                <div class="welcome-info">
                    <h1>Halo, <?= htmlspecialchars($nama); ?>!</h1>
                    <p>
                        <?php if ($role === 'admin'): ?>
                            Selamat bertugas di panel operasional. Pantau sirkulasi peminjaman buku, kelola anggota, dan perbarui koleksi perpustakaan dengan mudah.
                        <?php elseif ($role === 'kepala'): ?>
                            Selamat datang di panel eksekutif. Anda dapat memantau KPI perputaran buku, tren sirkulasi bulanan, dan mengekspor laporan resmi.
                        <?php else: ?>
                            Selamat datang di ruang baca digital Anda. Akses kartu anggota digital, pantau tanggal jatuh tempo buku, dan temukan inspirasi bacaan baru.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="welcome-badge">
                    <div style="font-size: 0.8rem; color: #94a3b8; margin-bottom: 4px;">Terdaftar Sejak</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: #ffffff;"><?= $user_since; ?></div>
                </div>
            </div>

            <!-- =============================================================
                 ROLE 1: ANGGOTA DASHBOARD
                 ============================================================= -->
            <?php if ($role === 'anggota'): ?>

                <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 24px; margin-bottom: 32px;">
                    <!-- Kartu Anggota Digital -->
                    <div class="digital-member-card">
                        <div class="card-topbar">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div class="brand-icon" style="width: 32px; height: 32px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                </div>
                                <span style="font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1rem;">KARTU ANGGOTA DIGITAL</span>
                            </div>
                            <div class="card-chip"></div>
                        </div>

                        <div style="margin-bottom: 24px;">
                            <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Nama Anggota</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: #ffffff;"><?= htmlspecialchars($nama); ?></div>
                            <div style="font-size: 0.85rem; color: #38bdf8; margin-top: 2px;">NIM / ID: <?= htmlspecialchars($nim_npp ?: 'MEM-' . str_pad($id_user, 5, '0', STR_PAD_LEFT)); ?></div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div>
                                <span class="barcode-visual">|||| | | ||| |||| |</span>
                                <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 4px;">Status: <strong style="color: #4ade80;">Aktif Berlaku</strong></div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: #fff !important;">
                                Cetak Kartu
                            </button>
                        </div>
                    </div>

                    <!-- Ringkasan Status Peminjaman Anggota -->
                    <div style="display: grid; grid-template-rows: auto 1fr; gap: 16px;">
                        <div class="metrics-grid" style="margin-bottom: 0;">
                            <div class="metric-card">
                                <div class="metric-content">
                                    <span class="metric-label">Sedang Dipinjam</span>
                                    <span class="metric-val" style="color: var(--primary);"><?= count($pinjaman_aktif_anggota); ?></span>
                                </div>
                                <div class="metric-icon-wrap icon-blue">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                                </div>
                            </div>

                            <div class="metric-card">
                                <div class="metric-content">
                                    <span class="metric-label">Total Riwayat</span>
                                    <span class="metric-val" style="color: var(--success);"><?= $total_pernah_pinjam; ?></span>
                                </div>
                                <div class="metric-icon-wrap icon-emerald">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                </div>
                            </div>
                        </div>

                        <div class="card" style="display: flex; flex-direction: column; justify-content: center;">
                            <h4 style="font-size: 1rem; margin-bottom: 6px;">Aturan Peminjaman</h4>
                            <p class="text-sm text-muted" style="line-height: 1.6;">
                                Anggota perpustakaan dapat meminjam buku maksimal 7 hari kalender. Harap mengembalikan buku tepat waktu untuk menjaga ketersediaan bagi pembaca lainnya.
                            </p>
                            <div style="margin-top: 12px;">
                                <a href="../View/katalog.php" class="btn btn-primary btn-sm">Jelajahi Katalog Buku &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buku yang Sedang Dipinjam Anggota -->
                <div class="card" style="margin-bottom: 28px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                        <div>
                            <h3 style="font-size: 1.25rem;">Buku yang Sedang Saya Pinjam</h3>
                            <p class="text-sm text-muted">Pantau tanggal peminjaman dan estimasi pengembalian buku.</p>
                        </div>
                        <span class="badge badge-primary"><?= count($pinjaman_aktif_anggota); ?> Buku Aktif</span>
                    </div>

                    <?php if (!empty($pinjaman_aktif_anggota)): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px;">
                            <?php foreach ($pinjaman_aktif_anggota as $item): ?>
                                <?php
                                    $tgl_pinjam = new DateTime($item['tanggal_pinjam']);
                                    $tgl_kembali_est = clone $tgl_pinjam;
                                    $tgl_kembali_est->modify('+7 days');
                                    $today = new DateTime();
                                    $interval = $today->diff($tgl_kembali_est);
                                    $is_overdue = $today > $tgl_kembali_est;
                                    $days_left = $interval->days;
                                    $cover_src = !empty($item['cover_path']) ? '../' . ltrim($item['cover_path'], '/') : 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=200&q=80';
                                ?>
                                <div style="background: var(--bg-page); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; display: flex; gap: 14px;">
                                    <img src="<?= htmlspecialchars($cover_src); ?>" alt="Cover" style="width: 70px; height: 95px; object-fit: cover; border-radius: 6px; box-shadow: var(--shadow-sm);">
                                    <div style="display: flex; flex-direction: column; justify-content: space-between; flex: 1;">
                                        <div>
                                            <h4 style="font-size: 0.95rem; margin-bottom: 2px;"><?= htmlspecialchars($item['judul']); ?></h4>
                                            <div class="text-xs text-muted">Penulis: <?= htmlspecialchars($item['penulis']); ?></div>
                                            <div class="text-xs text-muted" style="margin-top: 4px;">Dipinjam: <strong><?= date('d M Y', strtotime($item['tanggal_pinjam'])); ?></strong></div>
                                        </div>
                                        <div style="margin-top: 8px;">
                                            <?php if ($is_overdue): ?>
                                                <span class="badge badge-danger">Terlambat <?= $days_left; ?> Hari</span>
                                            <?php elseif ($days_left === 0): ?>
                                                <span class="badge badge-warning">Jatuh Tempo Hari Ini</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Sisa <?= $days_left; ?> Hari Lagi</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; color: #94a3b8;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                            <p style="font-weight: 600; font-size: 1.05rem;">Anda sedang tidak meminjam buku apapun.</p>
                            <p class="text-sm">Buku yang Anda pinjam akan ditampilkan di sini.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Riwayat Peminjaman Selesai -->
                <div class="card" id="riwayat">
                    <h3 style="font-size: 1.25rem; margin-bottom: 6px;">Riwayat Buku yang Telah Dikembalikan</h3>
                    <p class="text-sm text-muted" style="margin-bottom: 18px;">Catatan pengembalian buku yang pernah Anda pinjam sebelumnya.</p>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                            <thead>
                                <tr style="border-bottom: 1.5px solid var(--border-color); text-align: left; color: var(--text-muted);">
                                    <th style="padding: 12px 10px;">Judul Buku</th>
                                    <th style="padding: 12px 10px;">Penulis</th>
                                    <th style="padding: 12px 10px;">Tgl Pinjam</th>
                                    <th style="padding: 12px 10px;">Tgl Kembali</th>
                                    <th style="padding: 12px 10px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($riwayat_anggota)): ?>
                                    <?php foreach ($riwayat_anggota as $hist): ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                            <td style="padding: 12px 10px; font-weight: 600; color: var(--dark);"><?= htmlspecialchars($hist['judul']); ?></td>
                                            <td style="padding: 12px 10px; color: var(--text-muted);"><?= htmlspecialchars($hist['penulis']); ?></td>
                                            <td style="padding: 12px 10px;"><?= date('d M Y', strtotime($hist['tanggal_pinjam'])); ?></td>
                                            <td style="padding: 12px 10px;"><?= $hist['tanggal_kembali'] ? date('d M Y', strtotime($hist['tanggal_kembali'])) : '-'; ?></td>
                                            <td style="padding: 12px 10px;">
                                                <span class="badge badge-success">Sudah Dikembalikan</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted);">Belum ada riwayat pengembalian buku.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- =============================================================
                 ROLE 2: ADMIN DASHBOARD
                 ============================================================= -->
            <?php elseif ($role === 'admin'): ?>

                <!-- Metrics Grid -->
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-content">
                            <span class="metric-label">Total Judul Koleksi</span>
                            <span class="metric-val"><?= $total_buku; ?></span>
                        </div>
                        <div class="metric-icon-wrap icon-blue">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-content">
                            <span class="metric-label">Buku Sedang Dipinjam</span>
                            <span class="metric-val" style="color: var(--amber);"><?= $buku_dipinjam; ?></span>
                        </div>
                        <div class="metric-icon-wrap icon-amber">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-content">
                            <span class="metric-label">Total Anggota Aktif</span>
                            <span class="metric-val"><?= $total_anggota; ?></span>
                        </div>
                        <div class="metric-icon-wrap icon-cyan">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-content">
                            <span class="metric-label">Buku Telah Kembali</span>
                            <span class="metric-val" style="color: var(--success);"><?= $total_dikembalikan; ?></span>
                        </div>
                        <div class="metric-icon-wrap icon-emerald">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                    </div>
                </div>

                <!-- Admin Quick Actions Bar -->
                <div class="quick-actions-bar">
                    <a href="pinjam.php" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Catat Peminjaman Baru
                    </a>
                    <a href="../View/tambah_buku.php" class="btn btn-secondary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                        Tambah Buku
                    </a>
                    <a href="register.php" class="btn btn-secondary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                        Buat Anggota
                    </a>
                    <a href="export_laporan.php" class="btn btn-secondary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        Laporan Sirkulasi
                    </a>
                </div>

                <!-- Interactive Activity Chart & Recent Active Loans -->
                <div style="display: grid; grid-template-columns: 1.25fr 1fr; gap: 24px; margin-bottom: 32px;">
                    <!-- Chart Panel -->
                    <div class="card">
                        <div class="chart-header">
                            <div>
                                <h3 style="font-size: 1.2rem; margin-bottom: 4px;">Grafik Tren Peminjaman</h3>
                                <p class="text-sm text-muted">Aktivitas transaksi peminjaman buku perpustakaan.</p>
                            </div>
                            <div class="chart-period-tabs">
                                <button type="button" class="chart-tab-btn active" data-period="harian">Harian</button>
                                <button type="button" class="chart-tab-btn" data-period="bulanan">Bulanan</button>
                                <button type="button" class="chart-tab-btn" data-period="tahunan">Tahunan</button>
                            </div>
                        </div>
                        <div style="position: relative; height: 300px;">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>

                    <!-- Peminjaman Aktif Terbaru -->
                    <div class="card">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <div>
                                <h3 style="font-size: 1.2rem; margin-bottom: 4px;">Peminjaman Terkini</h3>
                                <p class="text-sm text-muted">Buku sedang dipinjam yang memerlukan pemantauan.</p>
                            </div>
                            <a href="export_laporan.php" class="text-sm font-semibold">Lihat Semua &rarr;</a>
                        </div>

                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                                <thead>
                                    <tr style="border-bottom: 1.5px solid var(--border-color); text-align: left; color: var(--text-muted);">
                                        <th style="padding: 8px 6px;">Peminjam</th>
                                        <th style="padding: 8px 6px;">Buku</th>
                                        <th style="padding: 8px 6px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($transaksi_aktif_admin)): ?>
                                        <?php foreach ($transaksi_aktif_admin as $trx): ?>
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="padding: 10px 6px;">
                                                    <div style="font-weight: 600;"><?= htmlspecialchars($trx['nama_lengkap']); ?></div>
                                                    <div class="text-xs text-muted"><?= date('d M', strtotime($trx['tanggal_pinjam'])); ?></div>
                                                </td>
                                                <td style="padding: 10px 6px; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?= htmlspecialchars($trx['judul']); ?>
                                                </td>
                                                <td style="padding: 10px 6px;">
                                                    <a href="kembali.php?id=<?= $trx['id_peminjaman']; ?>&id_buku=<?= $trx['id_buku']; ?>&ref=dashboard" class="btn btn-success btn-sm" onclick="return confirm('Proses pengembalian buku ini?');" style="padding: 4px 10px; font-size: 0.75rem;">
                                                        Kembali
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 24px; color: var(--text-muted);">Tidak ada transaksi pinjam yang aktif saat ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- =============================================================
                 ROLE 3: KEPALA PERPUSTAKAAN DASHBOARD
                 ============================================================= -->
            <?php elseif ($role === 'kepala'): ?>

                <!-- Executive KPIs -->
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-content">
                            <span class="metric-label">Volume Sirkulasi</span>
                            <span class="metric-val"><?= $buku_dipinjam + $total_dikembalikan; ?></span>
                        </div>
                        <div class="metric-icon-wrap icon-blue">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-content">
                            <span class="metric-label">Tingkat Sirkulasi Buku</span>
                            <span class="metric-val" style="color: var(--primary);">
                                <?= $total_buku > 0 ? round(($buku_dipinjam / $total_buku) * 100, 1) : 0; ?>%
                            </span>
                        </div>
                        <div class="metric-icon-wrap icon-cyan">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M16 12l-4-4-4 4M12 16V9"></path></svg>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-content">
                            <span class="metric-label">Tingkat Pengembalian</span>
                            <span class="metric-val" style="color: var(--success);">
                                <?php 
                                    $tot_trx = $buku_dipinjam + $total_dikembalikan;
                                    echo $tot_trx > 0 ? round(($total_dikembalikan / $tot_trx) * 100, 1) : 100;
                                ?>%
                            </span>
                        </div>
                        <div class="metric-icon-wrap icon-emerald">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-content">
                            <span class="metric-label">Anggota Perpustakaan</span>
                            <span class="metric-val"><?= $total_anggota; ?></span>
                        </div>
                        <div class="metric-icon-wrap icon-amber">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1.25fr 1fr; gap: 24px; margin-bottom: 32px;">
                    <!-- Grafik Sirkulasi -->
                    <div class="card">
                        <div class="chart-header">
                            <div>
                                <h3 style="font-size: 1.2rem; margin-bottom: 4px;">Analisis Sirkulasi Bulanan & Tahunan</h3>
                                <p class="text-sm text-muted">Pergerakan peminjaman buku untuk evaluasi kebijakan koleksi.</p>
                            </div>
                            <div class="chart-period-tabs">
                                <button type="button" class="chart-tab-btn" data-period="harian">Harian</button>
                                <button type="button" class="chart-tab-btn active" data-period="bulanan">Bulanan</button>
                                <button type="button" class="chart-tab-btn" data-period="tahunan">Tahunan</button>
                            </div>
                        </div>
                        <div style="position: relative; height: 300px;">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>

                    <!-- Top 5 Koleksi Terpopuler -->
                    <div class="card">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                            <div>
                                <h3 style="font-size: 1.2rem; margin-bottom: 4px;">Leaderboard Buku Terpopuler</h3>
                                <p class="text-sm text-muted">Koleksi yang paling diminati oleh anggota.</p>
                            </div>
                            <a href="export_laporan.php" class="btn btn-secondary btn-sm">Laporan Penuh</a>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php if (!empty($top_buku)): ?>
                                <?php $rank = 1; foreach ($top_buku as $tb): ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--bg-page); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <span style="width: 28px; height: 28px; border-radius: 50%; background: <?= $rank === 1 ? '#fbbf24' : ($rank === 2 ? '#cbd5e1' : '#e2e8f0'); ?>; color: var(--dark); font-weight: 700; font-size: 0.85rem; display: grid; place-items: center;">
                                                <?= $rank++; ?>
                                            </span>
                                            <div>
                                                <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($tb['judul']); ?></div>
                                                <div class="text-xs text-muted"><?= htmlspecialchars($tb['penulis']); ?></div>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <span class="badge badge-primary"><?= $tb['total_pinjam']; ?>x Dipinjam</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-sm text-muted" style="text-align: center; padding: 24px;">Belum ada data sirkulasi buku.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <!-- Chart.js Integration for Admin & Kepala -->
    <?php if ($role === 'admin' || $role === 'kepala'): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const chartData = <?= json_encode(['harian' => $chart_harian, 'bulanan' => $chart_bulanan, 'tahunan' => $chart_tahunan]); ?>;
            const ctx = document.getElementById('activityChart');
            let currentPeriod = '<?= $role === 'kepala' ? 'bulanan' : 'harian'; ?>';

            const myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData[currentPeriod].labels,
                    datasets: [{
                        label: 'Jumlah Peminjaman',
                        data: chartData[currentPeriod].values,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#4f46e5',
                        borderWidth: 2.5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { family: 'Plus Jakarta Sans', size: 13 },
                            bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                            padding: 10,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, font: { family: 'Plus Jakarta Sans', size: 11 } },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                        }
                    }
                }
            });

            document.querySelectorAll('.chart-tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.chart-tab-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const period = this.dataset.period;
                    myChart.data.labels = chartData[period].labels;
                    myChart.data.datasets[0].data = chartData[period].values;
                    myChart.update();
                });
            });
        </script>
    <?php endif; ?>

    <script>
    (function() {
        const dashboardSearch = document.getElementById('dashboardSearch');
        if (!dashboardSearch) return;

        dashboardSearch.addEventListener('keydown', function(event) {
            if (event.key !== 'Enter') return;

            const keyword = dashboardSearch.value.trim();
            if (keyword) {
                window.location.href = '../index.php?cari=' + encodeURIComponent(keyword) + '#katalog';
            }
        });
    })();
    </script>

</body>
</html>
