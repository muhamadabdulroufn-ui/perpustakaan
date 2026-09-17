<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../Model/config.php';

$role = $_SESSION['role'] ?? '';
$nama = $_SESSION['nama_lengkap'] ?? '';

// Hanya admin dan kepala yang boleh mengakses laporan sirkulasi
if ($role !== 'admin' && $role !== 'kepala') {
    header('Location: dashboard.php');
    exit;
}

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$status_filter = trim((string)($_GET['status'] ?? ''));

$where_clauses = ["MONTH(p.tanggal_pinjam) = $bulan", "YEAR(p.tanggal_pinjam) = $tahun"];
if ($status_filter === 'dipinjam' || $status_filter === 'kembali') {
    $where_clauses[] = "p.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}
$where_sql = implode(' AND ', $where_clauses);

$query = "SELECT p.*, u.nama_lengkap, u.nim_npp, b.judul 
          FROM peminjaman p
          JOIN users u ON p.id_user = u.id_user
          JOIN buku b ON p.id_buku = b.id_buku
          WHERE $where_sql
          ORDER BY p.tanggal_pinjam DESC";

$result = mysqli_query($conn, $query);
$rows = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
}

// -------------------------------------------------------------
// FITUR EKSPOR CSV / EXCEL
// -------------------------------------------------------------
if (isset($_GET['export']) && ($_GET['export'] === 'csv' || $_GET['export'] === 'excel')) {
    $filename = "laporan_sirkulasi_" . $tahun . "_" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . ".csv";
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM untuk Microsoft Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Header Kolom
    fputcsv($output, ['No', 'Nama Peminjam', 'NIM / NPP', 'Judul Buku', 'Tanggal Pinjam', 'Tanggal Kembali', 'Status']);

    $no = 1;
    foreach ($rows as $r) {
        fputcsv($output, [
            $no++,
            $r['nama_lengkap'],
            $r['nim_npp'] ?: '-',
            $r['judul'],
            $r['tanggal_pinjam'],
            $r['tanggal_kembali'] ?: '-',
            $r['status'] === 'dipinjam' ? 'Sedang Dipinjam' : 'Sudah Kembali'
        ]);
    }

    fclose($output);
    exit;
}

$total_transaksi = count($rows);
$total_aktif = count(array_filter($rows, static fn ($row) => $row['status'] === 'dipinjam'));
$total_selesai = $total_transaksi - $total_aktif;

$nama_bulan_arr = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$nama_bulan_pilihan = $nama_bulan_arr[$bulan] ?? date('F');

// Ambil nama Kepala Perpustakaan untuk tanda tangan cetak
$q_kepala = mysqli_query($conn, "SELECT nama_lengkap, nim_npp FROM users WHERE role = 'kepala' LIMIT 1");
$kepala_data = mysqli_fetch_assoc($q_kepala);
$nama_kepala = $kepala_data['nama_lengkap'] ?? 'Samsul Jonathan';
$npp_kepala = $kepala_data['nim_npp'] ?? '198507152010011002';

$pesan = $_GET['pesan'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Sirkulasi Perpustakaan - Ruang Baca</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../View/partials/admin_sidebar.css">
    <style>
        .filter-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: var(--shadow-sm);
        }
        .filter-form-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* Printable Official Letterhead styling */
        .print-header {
            display: none;
            text-align: center;
            border-bottom: 3px double #0f172a;
            padding-bottom: 14px;
            margin-bottom: 24px;
        }
        .print-signatures {
            display: none;
            margin-top: 48px;
            justify-content: flex-end;
        }
        .signature-box {
            text-align: center;
            width: 250px;
        }

        @media print {
            body.has-sidebar {
                display: block !important;
                background: #ffffff !important;
            }
            .app-sidebar, .app-topbar, .filter-card, .btn-action-col, .view-mode-hide, .sidebar-mobile-toggle, .topbar-actions {
                display: none !important;
            }
            .app-main {
                margin-left: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            .page-content {
                padding: 0 !important;
                max-width: 100% !important;
            }
            .print-header {
                display: block !important;
            }
            .print-signatures {
                display: flex !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #94a3b8 !important;
            }
            table th, table td {
                padding: 8px 10px !important;
                font-size: 0.8rem !important;
            }
        }
    </style>
</head>
<body class="has-sidebar">

    <!-- Admin Sidebar -->
    <?php 
        $active_menu = 'laporan'; 
        $admin_prefix = '../'; 
        include __DIR__ . '/../View/partials/admin_sidebar.php'; 
    ?>

    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <div class="topbar-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    <span>Pusat Laporan Sirkulasi</span>
                </div>
            </div>
            <div class="topbar-actions">
                <a href="dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
            </div>
        </header>

        <!-- Main Page Content -->
        <main class="page-content">

            <!-- Print Header (Hanya tampil saat Print) -->
            <div class="print-header">
                <h2 style="font-size: 1.4rem; font-weight: 800; text-transform: uppercase; margin-bottom: 2px;">Perpustakaan Ruang Baca</h2>
                <p style="font-size: 0.85rem; color: #475569; margin-bottom: 4px;">Sistem Informasi Sirkulasi & Manajemen Koleksi Terpadu</p>
                <h3 style="font-size: 1.1rem; margin-top: 10px; font-weight: 700;">
                    LAPORAN REKAPITULASI SIRKULASI PEMINJAMAN BUKU
                </h3>
                <p style="font-size: 0.85rem;">Periode: <strong><?= $nama_bulan_pilihan; ?> <?= $tahun; ?></strong></p>
            </div>

            <!-- Page Title & Actions -->
            <div class="view-mode-hide" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <p class="text-sm text-muted">Dashboard / Sirkulasi / Laporan Sirkulasi</p>
                    <h2>Laporan Sirkulasi Perpustakaan</h2>
                    <p class="text-sm text-muted">Rekapitulasi seluruh aktivitas peminjaman dan pengembalian buku.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <!-- Real Excel Download Button -->
                    <a href="export_laporan.php?bulan=<?= $bulan; ?>&tahun=<?= $tahun; ?>&status=<?= urlencode($status_filter); ?>&export=excel" class="btn btn-success btn-sm">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Unduh Excel / CSV
                    </a>
                    <!-- Print Button -->
                    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        Cetak Laporan Resmi
                    </button>
                </div>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-success view-mode-hide">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span><?= htmlspecialchars($pesan); ?></span>
                </div>
            <?php endif; ?>

            <!-- Metrics Summary Cards -->
            <div class="metrics-grid" style="margin-bottom: 24px;">
                <div class="metric-card">
                    <div class="metric-content">
                        <span class="metric-label">Total Transaksi</span>
                        <span class="metric-val"><?= $total_transaksi; ?></span>
                    </div>
                    <div class="metric-icon-wrap icon-blue">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-content">
                        <span class="metric-label">Sedang Dipinjam</span>
                        <span class="metric-val" style="color: var(--amber);"><?= $total_aktif; ?></span>
                    </div>
                    <div class="metric-icon-wrap icon-amber">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-content">
                        <span class="metric-label">Telah Dikembalikan</span>
                        <span class="metric-val" style="color: var(--success);"><?= $total_selesai; ?></span>
                    </div>
                    <div class="metric-icon-wrap icon-emerald">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="filter-card view-mode-hide">
                <form class="filter-form-row" method="GET" action="">
                    <div>
                        <label class="form-label" style="margin-bottom: 2px;">Bulan</label>
                        <select name="bulan" class="form-select" style="padding: 8px 12px; width: 140px;">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m; ?>" <?= $m === $bulan ? 'selected' : ''; ?>>
                                    <?= $nama_bulan_arr[$m]; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" style="margin-bottom: 2px;">Tahun</label>
                        <input type="number" name="tahun" class="form-input" value="<?= $tahun; ?>" min="2000" max="2099" style="padding: 8px 12px; width: 100px;">
                    </div>

                    <div>
                        <label class="form-label" style="margin-bottom: 2px;">Status</label>
                        <select name="status" class="form-select" style="padding: 8px 12px; width: 140px;">
                            <option value="">Semua Status</option>
                            <option value="dipinjam" <?= $status_filter === 'dipinjam' ? 'selected' : ''; ?>>Sedang Dipinjam</option>
                            <option value="kembali" <?= $status_filter === 'kembali' ? 'selected' : ''; ?>>Sudah Kembali</option>
                        </select>
                    </div>

                    <div style="margin-top: 18px;">
                        <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter</button>
                    </div>
                </form>

                <div style="width: 100%; max-width: 280px; margin-top: 10px;">
                    <input type="text" id="reportSearch" class="form-input" placeholder="Pencarian cepat tabel..." style="padding: 8px 14px; font-size: 0.85rem;">
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="background: var(--bg-page); border-bottom: 1.5px solid var(--border-color); text-align: left; color: var(--text-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em;">
                                <th style="padding: 14px 16px;">No</th>
                                <th style="padding: 14px 16px;">Nama Peminjam</th>
                                <th style="padding: 14px 16px;">NIM / Identitas</th>
                                <th style="padding: 14px 16px;">Judul Buku</th>
                                <th style="padding: 14px 16px;">Tgl Pinjam</th>
                                <th style="padding: 14px 16px;">Tgl Kembali</th>
                                <th style="padding: 14px 16px;">Status</th>
                                <th style="padding: 14px 16px;" class="btn-action-col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody">
                            <?php if (!empty($rows)): ?>
                                <?php $no = 1; foreach ($rows as $row): ?>
                                    <tr class="report-row" style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 14px 16px; color: var(--text-muted);"><?= $no++; ?></td>
                                        <td style="padding: 14px 16px; font-weight: 600; color: var(--dark);"><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                                        <td style="padding: 14px 16px; color: var(--text-muted);"><?= htmlspecialchars($row['nim_npp'] ?: '-'); ?></td>
                                        <td style="padding: 14px 16px; font-weight: 600; color: var(--primary);"><?= htmlspecialchars($row['judul']); ?></td>
                                        <td style="padding: 14px 16px;"><?= date('d M Y', strtotime($row['tanggal_pinjam'])); ?></td>
                                        <td style="padding: 14px 16px;"><?= $row['tanggal_kembali'] ? date('d M Y', strtotime($row['tanggal_kembali'])) : '-'; ?></td>
                                        <td style="padding: 14px 16px;">
                                            <?php if ($row['status'] === 'dipinjam'): ?>
                                                <span class="badge badge-warning"><span class="badge-dot"></span> Dipinjam</span>
                                            <?php else: ?>
                                                <span class="badge badge-success"><span class="badge-dot"></span> Kembali</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 14px 16px;" class="btn-action-col">
                                            <?php if ($row['status'] === 'dipinjam' && $role === 'admin'): ?>
                                                <a href="kembali.php?id=<?= $row['id_peminjaman']; ?>&id_buku=<?= $row['id_buku']; ?>&ref=laporan" class="btn btn-success btn-sm" onclick="return confirm('Proses pengembalian buku ini?');" style="padding: 4px 10px; font-size: 0.75rem;">
                                                    Proses Kembali
                                                </a>
                                            <?php else: ?>
                                                <span class="text-xs text-muted">Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 48px; color: var(--text-muted);">
                                        Tidak ada catatan transaksi peminjaman pada periode ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Printable Signatures Block (Hanya tampil saat Print) -->
            <div class="print-signatures">
                <div class="signature-box">
                    <p style="font-size: 0.85rem; margin-bottom: 60px;">
                        Mengetahui,<br>
                        <strong>Kepala Perpustakaan</strong>
                    </p>
                    <p style="font-weight: 700; text-decoration: underline; margin-bottom: 2px;">
                        <?= htmlspecialchars($nama_kepala); ?>
                    </p>
                    <p style="font-size: 0.8rem; color: #475569;">NIP / NPP: <?= htmlspecialchars($npp_kepala); ?></p>
                </div>
            </div>

        </main>
    </div>

    <!-- Live Search Script -->
    <script>
    (function() {
        const searchInput = document.getElementById('reportSearch');
        const rows = Array.from(document.querySelectorAll('.report-row'));

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = (!q || text.includes(q)) ? '' : 'none';
                });
            });
        }
    })();
    </script>
</body>
</html>