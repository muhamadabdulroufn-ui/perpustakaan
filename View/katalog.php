<?php
session_start();
require_once __DIR__ . '/../Model/config.php';

$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($conn, trim($_GET['cari'])) : '';
$query = "SELECT * FROM buku WHERE judul LIKE '%$keyword%' OR penulis LIKE '%$keyword%' ORDER BY judul ASC";
$result = mysqli_query($conn, $query);

$books = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $books[] = $row;
    }
}

$total_buku = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM buku"))['total'] ?? 0;
$total_tersedia = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM buku WHERE stok > 0"))['total'] ?? 0;
$logged_in = isset($_SESSION['id_user']);
$role = $_SESSION['role'] ?? '';

$cover_images = [
    'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1532012197267-da84d127e765?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=400&q=80',
    'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=400&q=80'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Koleksi Buku - Ruang Baca</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php if ($logged_in): ?>
        <link rel="stylesheet" href="partials/admin_sidebar.css">
    <?php endif; ?>
    <style>
        .catalog-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: <?= $logged_in ? '0' : '40px 20px 80px'; ?>;
        }
        .catalog-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .search-filter-box {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            flex: 1;
        }
        .catalog-search-input {
            width: 100%;
            max-width: 340px;
            padding: 10px 16px;
            border-radius: var(--radius-md);
            border: 1.5px solid var(--border-color);
            background: #ffffff;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: var(--transition-normal);
        }
        .catalog-search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }
        .view-switch-tabs {
            display: flex;
            background: #ffffff;
            border: 1px solid var(--border-color);
            padding: 4px;
            border-radius: var(--radius-md);
            gap: 4px;
        }
        .view-switch-btn {
            border: none;
            background: transparent;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition-fast);
        }
        .view-switch-btn.active {
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Grid View */
        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
        }
        .catalog-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: var(--transition-normal);
            box-shadow: var(--shadow-sm);
        }
        .catalog-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: #cbd5e1;
        }
        .card-cover-wrap {
            height: 200px;
            background: #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        .card-cover {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .catalog-card:hover .card-cover {
            transform: scale(1.05);
        }
        .card-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
            justify-content: space-between;
        }

        /* Table View */
        .catalog-table-panel {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .table-responsive {
            overflow-x: auto;
        }
        .catalog-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .catalog-table th {
            background: var(--bg-page);
            padding: 14px 18px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1.5px solid var(--border-color);
        }
        .catalog-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .catalog-table tr:hover {
            background: #f8fafc;
        }
        .table-thumb {
            width: 44px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="<?= $logged_in ? 'has-sidebar' : ''; ?>">

<?php if ($logged_in): ?>
    <?php 
        $active_menu = 'katalog'; 
        $admin_prefix = '../'; 
        include __DIR__ . '/partials/admin_sidebar.php'; 
    ?>
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-left">
                <span class="topbar-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                    Katalog Buku Lengkap
                </span>
            </div>
            <div class="topbar-actions">
                <?php if ($role === 'admin'): ?>
                    <a href="tambah_buku.php" class="btn btn-primary btn-sm">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Tambah Buku
                    </a>
                <?php endif; ?>
                <a href="../Controller/dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
            </div>
        </header>
        <main class="page-content">
<?php else: ?>
    <!-- Public Header -->
    <header class="navbar-public" style="margin-bottom: 30px;">
        <div class="navbar-inner">
            <a class="brand-public" href="../index.php">
                <span class="brand-badge"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg></span>
                <span>Ruang Baca</span>
            </a>
            <div class="nav-menu">
                <a href="../index.php" class="nav-item">&larr; Beranda</a>
                <a href="../Controller/login.php" class="btn btn-primary btn-sm">Masuk untuk Meminjam</a>
            </div>
        </div>
    </header>
    <main class="catalog-container">
<?php endif; ?>

        <!-- Catalog Header & Stats -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2>Eksplorasi Koleksi Buku</h2>
                <p class="text-sm text-muted">Jelajahi seluruh koleksi literatur fisik dan referensi yang tersedia di perpustakaan.</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <span class="badge badge-neutral" style="font-size: 0.85rem; padding: 6px 14px;">Total: <strong><?= $total_buku; ?></strong> Judul</span>
                <span class="badge badge-success" style="font-size: 0.85rem; padding: 6px 14px;">Tersedia: <strong><?= $total_tersedia; ?></strong> Judul</span>
            </div>
        </div>

        <!-- Catalog Interactive Toolbar -->
        <div class="catalog-toolbar">
            <div class="search-filter-box">
                <input type="text" id="catalogSearch" class="catalog-search-input" placeholder="Pencarian cepat judul atau penulis..." value="<?= htmlspecialchars($keyword); ?>">
                
                <div class="filter-tabs" style="background: #ffffff; box-shadow: var(--shadow-sm);">
                    <button type="button" class="tab-btn active" data-filter="all">Semua</button>
                    <button type="button" class="tab-btn" data-filter="available">Tersedia</button>
                    <button type="button" class="tab-btn" data-filter="empty">Dipinjam Semua</button>
                </div>
            </div>

            <!-- Dual View Switcher (Grid vs Table) -->
            <div class="view-switch-tabs">
                <button type="button" class="view-switch-btn active" id="btnViewGrid" title="Tampilan Grid Kartu">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Grid
                </button>
                <button type="button" class="view-switch-btn" id="btnViewTable" title="Tampilan Tabel Data">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    Tabel
                </button>
            </div>
        </div>

        <!-- 1. GRID VIEW -->
        <div class="catalog-grid" id="catalogGridView">
            <?php if (!empty($books)): ?>
                <?php foreach ($books as $b): ?>
                    <?php 
                        $cover_url = !empty($b['cover_path']) ? '../' . ltrim($b['cover_path'], '/') : $cover_images[(int)$b['id_buku'] % count($cover_images)];
                        $is_avail = (int)$b['stok'] > 0;
                    ?>
                    <article class="catalog-card" 
                             data-title="<?= htmlspecialchars($b['judul']); ?>" 
                             data-author="<?= htmlspecialchars($b['penulis']); ?>"
                             data-publisher="<?= htmlspecialchars($b['penerbit']); ?>"
                             data-year="<?= $b['tahun_terbit']; ?>"
                             data-stock="<?= $b['stok']; ?>"
                             data-cover="<?= htmlspecialchars($cover_url); ?>"
                             data-status="<?= $is_avail ? 'available' : 'empty'; ?>">
                        <div class="card-cover-wrap">
                            <img class="card-cover" src="<?= htmlspecialchars($cover_url); ?>" alt="<?= htmlspecialchars($b['judul']); ?>" loading="lazy">
                            <div style="position: absolute; top: 12px; right: 12px;">
                                <?php if ($is_avail): ?>
                                    <span class="badge badge-success"><span class="badge-dot"></span> <?= $b['stok']; ?> Eks</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><span class="badge-dot"></span> Habis</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div>
                                <h3 style="font-size: 1.05rem; margin-bottom: 4px;" title="<?= htmlspecialchars($b['judul']); ?>"><?= htmlspecialchars($b['judul']); ?></h3>
                                <p class="text-sm text-muted">Penulis: <strong><?= htmlspecialchars($b['penulis']); ?></strong></p>
                            </div>
                            <div style="border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 14px; display: flex; justify-content: space-between; align-items: center;">
                                <span class="text-xs text-muted"><?= htmlspecialchars($b['penerbit']); ?> &bull; <?= $b['tahun_terbit']; ?></span>
                                <button type="button" class="btn btn-secondary btn-sm btn-open-modal">Detail</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 2. TABLE VIEW -->
        <div class="catalog-table-panel" id="catalogTableView" style="display: none;">
            <div class="table-responsive">
                <table class="catalog-table">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Informasi Buku</th>
                            <th>Penerbit & Tahun</th>
                            <th>Stok Fisik</th>
                            <th>Ketersediaan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($books)): ?>
                            <?php foreach ($books as $b): ?>
                                <?php 
                                    $cover_url = !empty($b['cover_path']) ? '../' . ltrim($b['cover_path'], '/') : $cover_images[(int)$b['id_buku'] % count($cover_images)];
                                    $is_avail = (int)$b['stok'] > 0;
                                ?>
                                <tr class="table-row-item"
                                    data-title="<?= htmlspecialchars($b['judul']); ?>" 
                                    data-author="<?= htmlspecialchars($b['penulis']); ?>"
                                    data-publisher="<?= htmlspecialchars($b['penerbit']); ?>"
                                    data-year="<?= $b['tahun_terbit']; ?>"
                                    data-stock="<?= $b['stok']; ?>"
                                    data-cover="<?= htmlspecialchars($cover_url); ?>"
                                    data-status="<?= $is_avail ? 'available' : 'empty'; ?>">
                                    <td style="width: 60px;">
                                        <img src="<?= htmlspecialchars($cover_url); ?>" alt="Cover" class="table-thumb">
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--dark); font-size: 0.95rem;"><?= htmlspecialchars($b['judul']); ?></div>
                                        <div class="text-xs text-muted">Penulis: <?= htmlspecialchars($b['penulis']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($b['penerbit']); ?></div>
                                        <div class="text-xs text-muted">Tahun: <?= $b['tahun_terbit']; ?></div>
                                    </td>
                                    <td>
                                        <strong style="font-size: 1rem; color: <?= $is_avail ? 'var(--success-dark)' : 'var(--danger-dark)'; ?>;">
                                            <?= $b['stok']; ?>
                                        </strong> eksemplar
                                    </td>
                                    <td>
                                        <?php if ($is_avail): ?>
                                            <span class="badge badge-success"><span class="badge-dot"></span> Tersedia</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><span class="badge-dot"></span> Dipinjam</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-secondary btn-sm btn-open-modal">Lihat Detail</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty search placeholder -->
        <div id="catalogEmptyNotice" style="display: none; text-align: center; padding: 48px; background: #fff; border-radius: var(--radius-lg); margin-top: 24px; border: 1px solid var(--border-color);">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: #94a3b8; margin-bottom: 10px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <p style="font-weight: 700; font-size: 1.1rem; color: var(--dark);">Tidak ada koleksi buku yang cocok.</p>
            <p class="text-sm text-muted">Silakan coba dengan kata kunci pencarian judul atau nama penulis yang lain.</p>
        </div>

<?php if ($logged_in): ?>
        </main>
    </div>
<?php else: ?>
    </main>
<?php endif; ?>

    <!-- Interactive Book Detail Modal -->
    <div class="modal-backdrop" id="catalogModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalHeadingTitle">Informasi Koleksi Buku</h3>
                <button type="button" class="modal-close" id="modalCloseBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 140px 1fr; gap: 20px;">
                    <img id="modalCoverImg" src="" alt="Cover" style="width: 100%; height: 190px; object-fit: cover; border-radius: var(--radius-md); box-shadow: var(--shadow-md);">
                    <div>
                        <div id="modalBadgeStatus" style="margin-bottom: 8px;"></div>
                        <h4 id="modalBookTitle" style="font-size: 1.25rem; margin-bottom: 6px;">-</h4>
                        <p id="modalBookAuthor" class="text-muted text-sm" style="margin-bottom: 14px;">-</p>

                        <div style="background: var(--bg-page); padding: 12px; border-radius: var(--radius-md); font-size: 0.85rem; display: grid; gap: 6px;">
                            <div><strong>Penerbit:</strong> <span id="modalBookPublisher">-</span></div>
                            <div><strong>Tahun Terbit:</strong> <span id="modalBookYear">-</span></div>
                            <div><strong>Stok Tersedia:</strong> <span id="modalBookStock">-</span> eksemplar</div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-color);">
                    <h5 style="font-size: 0.95rem; margin-bottom: 4px;">Ketentuan Sirkulasi</h5>
                    <p class="text-sm text-muted">
                        Peminjaman buku dilayani setiap hari kerja. Durasi pinjam berlaku 7 hari kalender dan dapat diperpanjang jika belum ada reservasi dari anggota lain.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" id="modalCloseBtn2">Tutup</button>
                <?php if ($logged_in): ?>
                    <?php if ($role === 'admin'): ?>
                        <a href="../Controller/pinjam.php" class="btn btn-primary btn-sm">Buka Form Peminjaman</a>
                    <?php else: ?>
                        <a href="../Controller/dashboard.php" class="btn btn-primary btn-sm">Lihat Dashboard Saya</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../Controller/login.php" class="btn btn-primary btn-sm">Masuk untuk Meminjam</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Script Filter & Switcher -->
    <script>
    (function() {
        const catalogSearch = document.getElementById('catalogSearch');
        const tabBtns = document.querySelectorAll('.tab-btn');
        const gridCards = Array.from(document.querySelectorAll('.catalog-card'));
        const tableRows = Array.from(document.querySelectorAll('.table-row-item'));
        const emptyNotice = document.getElementById('catalogEmptyNotice');

        // View toggle
        const btnViewGrid = document.getElementById('btnViewGrid');
        const btnViewTable = document.getElementById('btnViewTable');
        const gridView = document.getElementById('catalogGridView');
        const tableView = document.getElementById('catalogTableView');

        let currentFilter = 'all';

        btnViewGrid.addEventListener('click', () => {
            btnViewGrid.classList.add('active');
            btnViewTable.classList.remove('active');
            gridView.style.display = 'grid';
            tableView.style.display = 'none';
        });

        btnViewTable.addEventListener('click', () => {
            btnViewTable.classList.add('active');
            btnViewGrid.classList.remove('active');
            gridView.style.display = 'none';
            tableView.style.display = 'block';
        });

        function applyFilter() {
            const query = catalogSearch.value.toLowerCase().trim();
            let visibleCount = 0;

            gridCards.forEach(card => {
                const title = (card.dataset.title || '').toLowerCase();
                const author = (card.dataset.author || '').toLowerCase();
                const status = card.dataset.status;

                const matchQ = !query || title.includes(query) || author.includes(query);
                const matchF = (currentFilter === 'all') || (status === currentFilter);

                if (matchQ && matchF) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            tableRows.forEach(row => {
                const title = (row.dataset.title || '').toLowerCase();
                const author = (row.dataset.author || '').toLowerCase();
                const status = row.dataset.status;

                const matchQ = !query || title.includes(query) || author.includes(query);
                const matchF = (currentFilter === 'all') || (status === currentFilter);

                row.style.display = (matchQ && matchF) ? '' : 'none';
            });

            emptyNotice.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        if (catalogSearch) catalogSearch.addEventListener('input', applyFilter);

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.filter;
                applyFilter();
            });
        });

        // Modal Handling
        const catalogModal = document.getElementById('catalogModal');
        const modalCloseBtn = document.getElementById('modalCloseBtn');
        const modalCloseBtn2 = document.getElementById('modalCloseBtn2');
        const modalHeadingTitle = document.getElementById('modalHeadingTitle');
        const modalBookTitle = document.getElementById('modalBookTitle');
        const modalBookAuthor = document.getElementById('modalBookAuthor');
        const modalBookPublisher = document.getElementById('modalBookPublisher');
        const modalBookYear = document.getElementById('modalBookYear');
        const modalBookStock = document.getElementById('modalBookStock');
        const modalCoverImg = document.getElementById('modalCoverImg');
        const modalBadgeStatus = document.getElementById('modalBadgeStatus');

        function openModalFromData(el) {
            const title = el.dataset.title;
            const author = el.dataset.author;
            const publisher = el.dataset.publisher;
            const year = el.dataset.year;
            const stock = parseInt(el.dataset.stock, 10);
            const cover = el.dataset.cover;

            modalBookTitle.textContent = title;
            modalBookAuthor.textContent = `Penulis: ${author}`;
            modalBookPublisher.textContent = publisher;
            modalBookYear.textContent = year;
            modalBookStock.textContent = stock;
            modalCoverImg.src = cover;

            if (stock > 0) {
                modalBadgeStatus.innerHTML = `<span class="badge badge-success"><span class="badge-dot"></span> Tersedia (${stock} eksemplar)</span>`;
            } else {
                modalBadgeStatus.innerHTML = `<span class="badge badge-danger"><span class="badge-dot"></span> Dipinjam Semua</span>`;
            }

            catalogModal.classList.add('active');
        }

        document.querySelectorAll('.btn-open-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const parent = btn.closest('.catalog-card') || btn.closest('.table-row-item');
                if (parent) openModalFromData(parent);
            });
        });

        function closeModal() {
            catalogModal.classList.remove('active');
        }

        if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
        if (modalCloseBtn2) modalCloseBtn2.addEventListener('click', closeModal);
        if (catalogModal) {
            catalogModal.addEventListener('click', (e) => {
                if (e.target === catalogModal) closeModal();
            });
        }
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
    })();
    </script>
</body>
</html>