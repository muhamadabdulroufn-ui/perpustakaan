<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../Model/config.php';
$role = $_SESSION['role'] ?? '';

// Proteksi akses: hanya admin yang dapat mencatat peminjaman
if ($role !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pesan = '';
$tipe_pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = (int)($_POST['id_user'] ?? 0);
    $id_buku = (int)($_POST['id_buku'] ?? 0);
    $tgl_pinjam = date('Y-m-d');

    if ($id_user <= 0 || $id_buku <= 0) {
        $pesan = "Harap pilih anggota dan buku yang valid.";
        $tipe_pesan = "danger";
    } else {
        // Cek stok buku
        $cek_stok = mysqli_query($conn, "SELECT judul, stok FROM buku WHERE id_buku = $id_buku");
        $buku = mysqli_fetch_assoc($cek_stok);

        if ($buku && $buku['stok'] > 0) {
            mysqli_begin_transaction($conn);
            try {
                // Catat peminjaman
                $stmt1 = mysqli_prepare($conn, "INSERT INTO peminjaman (id_user, id_buku, tanggal_pinjam, status) VALUES (?, ?, ?, 'dipinjam')");
                mysqli_stmt_bind_param($stmt1, "iis", $id_user, $id_buku, $tgl_pinjam);
                mysqli_stmt_execute($stmt1);
                mysqli_stmt_close($stmt1);

                // Kurangi stok buku
                mysqli_query($conn, "UPDATE buku SET stok = stok - 1 WHERE id_buku = $id_buku");

                mysqli_commit($conn);
                $pesan = "Transaksi peminjaman buku \"" . htmlspecialchars($buku['judul']) . "\" berhasil dicatat!";
                $tipe_pesan = "success";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $pesan = "Gagal memproses transaksi peminjaman ke sistem.";
                $tipe_pesan = "danger";
            }
        } else {
            $pesan = "Stok buku tidak mencukupi atau telah habis.";
            $tipe_pesan = "danger";
        }
    }
}

$users_query = mysqli_query($conn, "SELECT id_user, nama_lengkap, nim_npp FROM users WHERE role = 'anggota' ORDER BY nama_lengkap ASC");
$users = [];
if ($users_query) {
    while ($u = mysqli_fetch_assoc($users_query)) {
        $users[] = $u;
    }
}

$bukus_query = mysqli_query($conn, "SELECT id_buku, judul, penulis, penerbit, tahun_terbit, stok, cover_path FROM buku WHERE stok > 0 ORDER BY judul ASC");
$bukus = [];
if ($bukus_query) {
    while ($b = mysqli_fetch_assoc($bukus_query)) {
        $bukus[] = $b;
    }
}

$total_anggota = count($users);
$total_buku_tersedia = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(stok), 0) AS total FROM buku WHERE stok > 0"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Buku - Ruang Baca</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../View/partials/admin_sidebar.css">
    <style>
        .checkout-preview-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 28px;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 90px;
        }
        .step-indicator {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        .step-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            padding: 8px 14px;
            border-radius: var(--radius-full);
            background: #ffffff;
            border: 1px solid var(--border-color);
        }
        .step-pill.active {
            color: var(--primary);
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .step-num {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: currentColor;
            color: #ffffff;
            font-size: 0.75rem;
            display: grid;
            place-items: center;
        }
    </style>
</head>
<body class="has-sidebar">

    <!-- Admin Sidebar -->
    <?php 
        $active_menu = 'pinjam'; 
        $admin_prefix = '../'; 
        include __DIR__ . '/../View/partials/admin_sidebar.php'; 
    ?>

    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <div class="topbar-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5"></path><path d="m21 3-7 7"></path><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"></path></svg>
                    <span>Layanan Transaksi Peminjaman</span>
                </div>
            </div>
            <div class="topbar-actions">
                <a href="dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
            </div>
        </header>

        <!-- Main Page Content -->
        <main class="page-content">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <p class="text-sm text-muted">Dashboard / Sirkulasi / Peminjaman Buku</p>
                    <h2>Catat Transaksi Peminjaman</h2>
                    <p class="text-sm text-muted">Proses peminjaman buku oleh anggota perpustakaan dengan cepat dan akurat.</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <span class="badge badge-neutral" style="padding: 8px 14px;">Anggota Aktif: <strong><?= $total_anggota; ?></strong></span>
                    <span class="badge badge-primary" style="padding: 8px 14px;">Eksemplar Siap: <strong><?= $total_buku_tersedia; ?></strong></span>
                </div>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?= $tipe_pesan; ?>">
                    <?php if ($tipe_pesan === 'success'): ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?php else: ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php endif; ?>
                    <span><?= $pesan; ?></span>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 28px; align-items: start;">
                <!-- Form Input Transaksi -->
                <div class="card">
                    <div class="step-indicator">
                        <div class="step-pill active" id="pillMember">
                            <span class="step-num">1</span>
                            <span>Pilih Anggota</span>
                        </div>
                        <div class="step-pill" id="pillBook">
                            <span class="step-num">2</span>
                            <span>Pilih Buku</span>
                        </div>
                        <div class="step-pill" id="pillConfirm">
                            <span class="step-num">3</span>
                            <span>Konfirmasi</span>
                        </div>
                    </div>

                    <form method="POST" action="" id="loanForm">
                        <div class="form-group" style="margin-bottom: 22px;">
                            <label class="form-label" for="id_user">Pilih Anggota Peminjam</label>
                            <select id="id_user" name="id_user" class="form-select" required>
                                <option value="" disabled selected>-- Pilih salah satu anggota --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id_user']; ?>" 
                                            data-name="<?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES); ?>" 
                                            data-nim="<?= htmlspecialchars($u['nim_npp'] ?: 'Tanpa NIM', ENT_QUOTES); ?>">
                                        <?= htmlspecialchars($u['nama_lengkap']); ?> (<?= htmlspecialchars($u['nim_npp'] ?: 'Anggota'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 22px;">
                            <label class="form-label" for="id_buku">Pilih Judul Buku yang Dipinjam</label>
                            <select id="id_buku" name="id_buku" class="form-select" required>
                                <option value="" disabled selected>-- Pilih buku yang tersedia --</option>
                                <?php foreach ($bukus as $b): ?>
                                    <?php 
                                        $cover = !empty($b['cover_path']) ? '../' . ltrim($b['cover_path'], '/') : 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=200&q=80';
                                    ?>
                                    <option value="<?= $b['id_buku']; ?>" 
                                            data-title="<?= htmlspecialchars($b['judul'], ENT_QUOTES); ?>"
                                            data-author="<?= htmlspecialchars($b['penulis'], ENT_QUOTES); ?>"
                                            data-stock="<?= $b['stok']; ?>"
                                            data-cover="<?= htmlspecialchars($cover, ENT_QUOTES); ?>">
                                        <?= htmlspecialchars($b['judul']); ?> &mdash; Sisa <?= $b['stok']; ?> eks (<?= htmlspecialchars($b['penulis']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="background: var(--bg-page); border-radius: var(--radius-md); padding: 18px; margin-bottom: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div>
                                <span class="text-xs text-muted" style="text-transform: uppercase; font-weight: 700;">Tanggal Peminjaman</span>
                                <div style="font-weight: 700; font-size: 1rem; color: var(--dark); margin-top: 2px;">
                                    <?= date('d M Y'); ?> (Hari ini)
                                </div>
                            </div>
                            <div>
                                <span class="text-xs text-muted" style="text-transform: uppercase; font-weight: 700;">Estimasi Jatuh Tempo</span>
                                <div style="font-weight: 700; font-size: 1rem; color: var(--primary); margin-top: 2px;">
                                    <?= date('d M Y', strtotime('+7 days')); ?> (+7 hari)
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg" id="btnSubmitLoan" style="width: 100%;" disabled>
                            Simpan Transaksi Peminjaman
                        </button>
                    </form>
                </div>

                <!-- Live Transaction Checkout Receipt Preview -->
                <div class="checkout-preview-card">
                    <h3 style="font-size: 1.2rem; margin-bottom: 4px;">Ringkasan Sirkulasi</h3>
                    <p class="text-sm text-muted" style="margin-bottom: 20px;">Pratinjau data transaksi sebelum disimpan.</p>

                    <!-- Anggota Preview Box -->
                    <div style="padding: 14px; background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 16px; display: flex; align-items: center; gap: 14px;">
                        <div id="previewAvatar" style="width: 44px; height: 44px; border-radius: 12px; background: #e2e8f0; color: #64748b; font-weight: 700; font-size: 1.1rem; display: grid; place-items: center; flex-shrink: 0;">
                            ?
                        </div>
                        <div style="overflow: hidden;">
                            <span class="text-xs text-muted" style="text-transform: uppercase; font-weight: 700;">Identitas Anggota</span>
                            <div id="previewMemberName" style="font-weight: 700; color: var(--dark); font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                Belum memilih anggota
                            </div>
                            <div id="previewMemberNim" class="text-xs text-muted">-</div>
                        </div>
                    </div>

                    <!-- Buku Preview Box -->
                    <div style="padding: 14px; background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 16px; display: flex; gap: 14px;">
                        <img id="previewBookCover" src="https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=200&q=80" alt="Cover" style="width: 50px; height: 70px; object-fit: cover; border-radius: 6px; box-shadow: var(--shadow-sm); flex-shrink: 0; opacity: 0.5;">
                        <div style="display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; flex: 1;">
                            <div>
                                <span class="text-xs text-muted" style="text-transform: uppercase; font-weight: 700;">Buku yang Dipinjam</span>
                                <div id="previewBookTitle" style="font-weight: 700; color: var(--dark); font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    Belum memilih buku
                                </div>
                                <div id="previewBookAuthor" class="text-xs text-muted">-</div>
                            </div>
                            <div id="previewStockStatus" style="font-size: 0.75rem; color: var(--text-muted);">
                                Stok tersedia: -
                            </div>
                        </div>
                    </div>

                    <!-- Status Box -->
                    <div style="padding: 14px; border-radius: var(--radius-md); background: #f1f5f9; text-align: center; font-size: 0.85rem;" id="previewStatusBox">
                        <span style="color: var(--text-muted); font-weight: 600;">Lengkapi pilihan anggota dan buku di samping.</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Script Interactive Live Preview -->
    <script>
    (function() {
        const userSelect = document.getElementById('id_user');
        const bookSelect = document.getElementById('id_buku');
        const btnSubmit = document.getElementById('btnSubmitLoan');
        const form = document.getElementById('loanForm');

        // Preview Elements
        const previewAvatar = document.getElementById('previewAvatar');
        const previewMemberName = document.getElementById('previewMemberName');
        const previewMemberNim = document.getElementById('previewMemberNim');
        const previewBookCover = document.getElementById('previewBookCover');
        const previewBookTitle = document.getElementById('previewBookTitle');
        const previewBookAuthor = document.getElementById('previewBookAuthor');
        const previewStockStatus = document.getElementById('previewStockStatus');
        const previewStatusBox = document.getElementById('previewStatusBox');

        const pillMember = document.getElementById('pillMember');
        const pillBook = document.getElementById('pillBook');
        const pillConfirm = document.getElementById('pillConfirm');

        function updatePreview() {
            const selectedUser = userSelect.options[userSelect.selectedIndex];
            const selectedBook = bookSelect.options[bookSelect.selectedIndex];

            const hasUser = selectedUser && selectedUser.value !== "";
            const hasBook = selectedBook && selectedBook.value !== "";

            if (hasUser) {
                const name = selectedUser.dataset.name;
                const nim = selectedUser.dataset.nim;
                previewMemberName.textContent = name;
                previewMemberNim.textContent = `NIM: ${nim}`;
                previewAvatar.textContent = name.charAt(0).toUpperCase();
                previewAvatar.style.background = 'linear-gradient(135deg, #4f46e5, #06b6d4)';
                previewAvatar.style.color = '#fff';
                pillMember.classList.add('active');
            } else {
                previewMemberName.textContent = 'Belum memilih anggota';
                previewMemberNim.textContent = '-';
                previewAvatar.textContent = '?';
                previewAvatar.style.background = '#e2e8f0';
                previewAvatar.style.color = '#64748b';
                pillMember.classList.remove('active');
            }

            if (hasBook) {
                const title = selectedBook.dataset.title;
                const author = selectedBook.dataset.author;
                const stock = parseInt(selectedBook.dataset.stock, 10);
                const cover = selectedBook.dataset.cover;

                previewBookTitle.textContent = title;
                previewBookAuthor.textContent = `oleh ${author}`;
                previewBookCover.src = cover;
                previewBookCover.style.opacity = '1';
                previewStockStatus.innerHTML = `<span style="color: #059669; font-weight: 700;">${stock} eksemplar tersedia</span> &rarr; berkurang jadi <strong>${stock - 1}</strong>`;
                pillBook.classList.add('active');
            } else {
                previewBookTitle.textContent = 'Belum memilih buku';
                previewBookAuthor.textContent = '-';
                previewBookCover.style.opacity = '0.5';
                previewStockStatus.textContent = 'Stok tersedia: -';
                pillBook.classList.remove('active');
            }

            if (hasUser && hasBook) {
                btnSubmit.disabled = false;
                pillConfirm.classList.add('active');
                previewStatusBox.style.background = '#ecfdf5';
                previewStatusBox.innerHTML = '<strong style="color: #059669;">✓ Data transaksi siap disimpan</strong>';
            } else {
                btnSubmit.disabled = true;
                pillConfirm.classList.remove('active');
                previewStatusBox.style.background = '#f1f5f9';
                previewStatusBox.innerHTML = '<span style="color: #64748b;">Lengkapi pilihan anggota dan buku di samping.</span>';
            }
        }

        userSelect.addEventListener('change', updatePreview);
        bookSelect.addEventListener('change', updatePreview);

        form.addEventListener('submit', function(e) {
            const memberName = previewMemberName.textContent;
            const bookTitle = previewBookTitle.textContent;
            if (!confirm(`Konfirmasi peminjaman buku:\n"${bookTitle}"\noleh ${memberName}?`)) {
                e.preventDefault();
            }
        });
    })();
    </script>
</body>
</html>