<?php
session_start();

// Proteksi akses: Wajib login dan hanya role 'admin'
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die("Akses ditolak. Halaman ini khusus untuk Petugas Perpustakaan. <a href='../index.php'>Kembali ke Beranda</a>");
}

require_once __DIR__ . '/../Model/config.php';

$pesan = '';
$tipe_pesan = '';
$cover_path = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul        = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $penulis      = mysqli_real_escape_string($conn, trim($_POST['penulis']));
    $penerbit     = mysqli_real_escape_string($conn, trim($_POST['penerbit']));
    $tahun_terbit = (int)$_POST['tahun_terbit'];
    $stok         = (int)$_POST['stok'];
    $cover_file   = $_FILES['cover'] ?? null;

    if ($cover_file && $cover_file['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $file_type = mime_content_type($cover_file['tmp_name']);

        if ($cover_file['error'] !== UPLOAD_ERR_OK || !isset($allowed_types[$file_type])) {
            $pesan = 'File cover harus berupa gambar JPG, PNG, atau WEBP yang valid.';
            $tipe_pesan = 'danger';
        } elseif ($cover_file['size'] > 5 * 1024 * 1024) {
            $pesan = 'Ukuran file cover maksimal adalah 5 MB.';
            $tipe_pesan = 'danger';
        } else {
            $upload_dir = __DIR__ . '/../Model/uploads/covers';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_name = bin2hex(random_bytes(12)) . '.' . $allowed_types[$file_type];
            if (move_uploaded_file($cover_file['tmp_name'], $upload_dir . '/' . $file_name)) {
                $cover_path = 'Model/uploads/covers/' . $file_name;
            } else {
                $pesan = 'Cover gagal diunggah ke server. Silakan periksa izin folder upload.';
                $tipe_pesan = 'danger';
            }
        }
    }

    if (!$pesan && (empty($judul) || empty($penulis) || empty($penerbit) || $tahun_terbit <= 0 || $stok < 0)) {
        $pesan = 'Harap isi seluruh kolom formulir dengan data yang valid.';
        $tipe_pesan = 'danger';
    } elseif (!$pesan) {
        $stmt = mysqli_prepare($conn, "INSERT INTO buku (judul, penulis, penerbit, tahun_terbit, stok, cover_path) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssiss", $judul, $penulis, $penerbit, $tahun_terbit, $stok, $cover_path);

        if (mysqli_stmt_execute($stmt)) {
            $pesan = 'Buku "' . htmlspecialchars($judul) . '" berhasil ditambahkan ke dalam katalog!';
            $tipe_pesan = 'success';
        } else {
            $pesan = 'Gagal menyimpan buku ke database: ' . mysqli_error($conn);
            $tipe_pesan = 'danger';
        }
        mysqli_stmt_close($stmt);
    }
}

// Mengambil koleksi terbaru
$query_terbaru = "SELECT * FROM buku ORDER BY id_buku DESC LIMIT 6";
$result_terbaru = mysqli_query($conn, $query_terbaru);
$total_koleksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM buku"))['total'] ?? 0;
$total_stok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(stok), 0) AS total FROM buku"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku Baru - Ruang Baca</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="partials/admin_sidebar.css">
    <style>
        .cover-dropzone {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            text-align: center;
            background: var(--bg-page);
            cursor: pointer;
            transition: var(--transition-normal);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }
        .cover-dropzone:hover, .cover-dropzone.dragover {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .cover-preview-img {
            max-height: 160px;
            max-width: 130px;
            border-radius: var(--radius-md);
            object-fit: cover;
            box-shadow: var(--shadow-md);
            display: none;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="has-sidebar">

    <!-- Admin Sidebar -->
    <?php 
        $active_menu = 'tambah'; 
        $admin_prefix = '../'; 
        include __DIR__ . '/partials/admin_sidebar.php'; 
    ?>

    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <div class="topbar-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    <span>Penambahan Koleksi Buku</span>
                </div>
            </div>
            <div class="topbar-actions">
                <a href="katalog.php" class="btn btn-secondary btn-sm">Buka Katalog</a>
                <a href="../Controller/dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
            </div>
        </header>

        <!-- Main Page Content -->
        <main class="page-content">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <p class="text-sm text-muted">Dashboard / Katalog / Tambah Buku Baru</p>
                    <h2>Tambah Koleksi Buku</h2>
                    <p class="text-sm text-muted">Lengkapi formulir untuk menambahkan judul buku baru ke dalam database perpustakaan.</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <span class="badge badge-neutral" style="padding: 8px 14px; font-size: 0.85rem;">Total Judul: <strong><?= $total_koleksi; ?></strong></span>
                    <span class="badge badge-primary" style="padding: 8px 14px; font-size: 0.85rem;">Total Stok Fisik: <strong><?= $total_stok; ?> Eks</strong></span>
                </div>
            </div>

            <?php if ($pesan): ?>
                <div class="alert alert-<?= $tipe_pesan; ?>">
                    <?php if ($tipe_pesan === 'success'): ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?php else: ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php endif; ?>
                    <span><?= $pesan; ?></span>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 28px; align-items: start;">
                <!-- Form Input Buku -->
                <div class="card">
                    <h3 style="font-size: 1.25rem; margin-bottom: 6px;">Detail Data Buku</h3>
                    <p class="text-sm text-muted" style="margin-bottom: 20px;">Masukkan informasi bibliografi buku secara lengkap.</p>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label" for="judul">Judul Buku Lengkap</label>
                            <input type="text" id="judul" name="judul" class="form-input" placeholder="Contoh: Algoritma dan Struktur Data Lanjut" required autofocus>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label" for="penulis">Nama Penulis / Pengarang</label>
                                <input type="text" id="penulis" name="penulis" class="form-input" placeholder="Nama penulis" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="penerbit">Penerbit</label>
                                <input type="text" id="penerbit" name="penerbit" class="form-input" placeholder="Nama penerbit" required>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label" for="tahun_terbit">Tahun Terbit</label>
                                <input type="number" id="tahun_terbit" name="tahun_terbit" class="form-input" min="1900" max="2099" value="<?= date('Y'); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="stok">Jumlah Stok Tersedia (Eksemplar)</label>
                                <input type="number" id="stok" name="stok" class="form-input" min="0" value="1" required>
                            </div>
                        </div>

                        <!-- Live Cover Drag and Drop Zone -->
                        <div class="form-group">
                            <label class="form-label">Cover Buku (Opsional, JPG/PNG/WEBP maks 5 MB)</label>
                            <label class="cover-dropzone" for="coverInput" id="dropzoneBox">
                                <img src="" alt="Preview Cover" class="cover-preview-img" id="coverPreview">
                                <div id="dropzonePrompt">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--primary); margin-bottom: 8px;">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    <div style="font-weight: 600; font-size: 0.9rem; color: var(--dark);">Pilih gambar cover atau tarik ke sini</div>
                                    <div class="text-xs text-muted" style="margin-top: 4px;">JPG, PNG, atau WEBP hingga 5 MB</div>
                                </div>
                                <input type="file" id="coverInput" name="cover" accept="image/jpeg,image/png,image/webp" style="display: none;">
                            </label>
                        </div>

                        <div style="display: flex; gap: 12px; margin-top: 10px;">
                            <button type="reset" class="btn btn-secondary">Reset Form</button>
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan ke Katalog Perpustakaan</button>
                        </div>
                    </form>
                </div>

                <!-- Recent Added Books Panel -->
                <div class="card">
                    <h3 style="font-size: 1.2rem; margin-bottom: 6px;">Buku Terbaru yang Ditambahkan</h3>
                    <p class="text-sm text-muted" style="margin-bottom: 16px;">Koleksi terakhir yang baru saja diinput ke sistem.</p>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if (mysqli_num_rows($result_terbaru) > 0): ?>
                            <?php while ($buku = mysqli_fetch_assoc($result_terbaru)): ?>
                                <div style="display: flex; gap: 12px; align-items: center; padding: 10px 12px; background: var(--bg-page); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                    <?php 
                                        $cover_thumb = !empty($buku['cover_path']) ? '../' . ltrim($buku['cover_path'], '/') : 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=150&q=80';
                                    ?>
                                    <img src="<?= htmlspecialchars($cover_thumb); ?>" alt="Thumb" style="width: 44px; height: 60px; object-fit: cover; border-radius: 6px; box-shadow: var(--shadow-sm); flex-shrink: 0;">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-weight: 600; font-size: 0.9rem; color: var(--dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars($buku['judul']); ?>
                                        </div>
                                        <div class="text-xs text-muted">oleh <?= htmlspecialchars($buku['penulis']); ?> &bull; <?= $buku['tahun_terbit']; ?></div>
                                        <div style="margin-top: 4px;">
                                            <span class="badge badge-success" style="font-size: 0.7rem; padding: 2px 8px;">Stok: <?= $buku['stok']; ?> Eks</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-sm text-muted" style="text-align: center; padding: 24px;">Belum ada koleksi buku di database.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Live Drag and Drop Cover Preview Script -->
    <script>
    (function() {
        const coverInput = document.getElementById('coverInput');
        const coverPreview = document.getElementById('coverPreview');
        const dropzonePrompt = document.getElementById('dropzonePrompt');
        const dropzoneBox = document.getElementById('dropzoneBox');

        function showPreview(file) {
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    coverPreview.src = e.target.result;
                    coverPreview.style.display = 'block';
                    dropzonePrompt.querySelector('div').textContent = `File dipilih: ${file.name}`;
                };
                reader.readAsDataURL(file);
            }
        }

        coverInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                showPreview(this.files[0]);
            }
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzoneBox.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzoneBox.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzoneBox.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzoneBox.classList.remove('dragover');
            });
        });

        dropzoneBox.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files[0]) {
                coverInput.files = files;
                showPreview(files[0]);
            }
        });
    })();
    </script>
</body>
</html>