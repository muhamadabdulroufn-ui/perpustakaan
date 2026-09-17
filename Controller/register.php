<?php
session_start();
require_once __DIR__ . '/../Model/config.php';

$is_admin = isset($_SESSION['id_user']) && ($_SESSION['role'] ?? '') === 'admin';

// Jika sudah login tapi bukan admin, arahkan ke dashboard
if (isset($_SESSION['id_user']) && !$is_admin) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $nama_lengkap = trim((string)($_POST['nama_lengkap'] ?? ''));
    $nim_npp = trim((string)($_POST['nim_npp'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $konfirmasi_password = (string)($_POST['konfirmasi_password'] ?? '');

    if ($username === '' || $nama_lengkap === '' || $nim_npp === '' || $password === '' || $konfirmasi_password === '') {
        $error = 'Semua kolom formulir wajib diisi.';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal terdiri dari 3 karakter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal terdiri dari 6 karakter.';
    } elseif (strlen($nim_npp) > 30) {
        $error = 'NIM / NPP maksimal terdiri dari 30 karakter.';
    } elseif ($password !== $konfirmasi_password) {
        $error = 'Konfirmasi password tidak cocok dengan password yang dimasukkan.';
    } else {
        $check = mysqli_prepare($conn, 'SELECT COUNT(*) FROM users WHERE username = ?');
        mysqli_stmt_bind_param($check, 's', $username);
        mysqli_stmt_execute($check);
        mysqli_stmt_bind_result($check, $jumlah_username);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);

        if ($jumlah_username > 0) {
            $error = 'Username sudah digunakan oleh akun lain. Silakan gunakan username berbeda.';
        } else {
            $hash_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, 'INSERT INTO users (username, password, nama_lengkap, nim_npp, role) VALUES (?, ?, ?, ?, ?)');
            $role = 'anggota';
            mysqli_stmt_bind_param($stmt, 'sssss', $username, $hash_password, $nama_lengkap, $nim_npp, $role);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                if ($is_admin) {
                    $success = 'Anggota baru atas nama "' . htmlspecialchars($nama_lengkap) . '" berhasil ditambahkan!';
                } else {
                    header('Location: login.php?registered=1');
                    exit;
                }
            } else {
                mysqli_stmt_close($stmt);
                $error = 'Pendaftaran gagal disimpan ke database. Silakan coba kembali.';
            }
        }
    }
}

// Data daftar anggota untuk admin view
$members = [];
if ($is_admin) {
    $q_members = mysqli_query($conn, "SELECT id_user, username, nama_lengkap, nim_npp, created_at FROM users WHERE role = 'anggota' ORDER BY id_user DESC LIMIT 10");
    if ($q_members) {
        while ($row = mysqli_fetch_assoc($q_members)) {
            $members[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_admin ? 'Kelola & Buat Anggota' : 'Daftar Anggota'; ?> - Ruang Baca</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php if ($is_admin): ?>
        <link rel="stylesheet" href="../View/partials/admin_sidebar.css">
    <?php endif; ?>
    <style>
        <?php if (!$is_admin): ?>
        body.public-register {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 16px;
            background: radial-gradient(circle at 85% 15%, rgba(6, 182, 212, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 15% 85%, rgba(79, 70, 229, 0.18) 0%, transparent 40%),
                        #0f172a;
        }
        .register-container {
            width: 100%;
            max-width: 980px;
            background: #ffffff;
            border-radius: var(--radius-xl);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.5);
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .register-hero {
            background: linear-gradient(145deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            color: #ffffff;
            padding: 44px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .register-hero::before {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.25) 0%, transparent 70%);
            top: -40px;
            right: -40px;
        }
        .register-form-wrap {
            padding: 44px clamp(24px, 5vw, 56px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        @media (max-width: 800px) {
            .register-container { grid-template-columns: 1fr; }
            .register-hero { padding: 32px 24px; }
            .register-form-wrap { padding: 32px 20px; }
        }
        <?php endif; ?>
    </style>
</head>
<body class="<?= $is_admin ? 'has-sidebar' : 'public-register'; ?>">

<?php if ($is_admin): ?>
    <!-- Admin Sidebar Navigation -->
    <?php 
        $active_menu = 'anggota'; 
        $admin_prefix = '../'; 
        include __DIR__ . '/../View/partials/admin_sidebar.php'; 
    ?>

    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <div class="topbar-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <line x1="19" y1="8" x2="19" y2="14"></line>
                        <line x1="22" y1="11" x2="16" y2="11"></line>
                    </svg>
                    <span>Manajemen Anggota Perpustakaan</span>
                </div>
            </div>
            <div class="topbar-actions">
                <a href="dashboard.php" class="btn btn-secondary btn-sm">Kembali ke Dashboard</a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            <div style="margin-bottom: 24px;">
                <p class="text-sm text-muted">Dashboard / Anggota / Buat Anggota Baru</p>
                <h2>Pendaftaran & Kelola Akun Anggota</h2>
                <p class="text-muted text-sm">Tambahkan anggota baru ke sistem untuk memberikan hak akses peminjaman buku perpustakaan.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; align-items: start;">
                <!-- Form Card -->
                <div class="card">
                    <h3 style="font-size: 1.25rem; margin-bottom: 6px;">Formulir Anggota Baru</h3>
                    <p class="text-sm text-muted" style="margin-bottom: 20px;">Pastikan data identitas mahasiswa/anggota diisi dengan akurat.</p>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label" for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-input" placeholder="Contoh: Ahmad Dahlan" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label" for="nim_npp">NIM / NPP / No. Identitas</label>
                                <input type="text" id="nim_npp" name="nim_npp" class="form-input" placeholder="Contoh: 210102003" value="<?= htmlspecialchars($_POST['nim_npp'] ?? ''); ?>" required maxlength="30">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="username">Username Login</label>
                                <input type="text" id="username" name="username" class="form-input" placeholder="Contoh: ahmad" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label" for="password">Password</label>
                                <input type="password" id="password" name="password" class="form-input" placeholder="Minimal 6 karakter" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="konfirmasi_password">Konfirmasi Password</label>
                                <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-input" placeholder="Ulangi password" required>
                            </div>
                        </div>

                        <div style="margin-top: 14px; display: flex; gap: 12px;">
                            <button type="reset" class="btn btn-secondary">Reset Formulir</button>
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan Akun Anggota</button>
                        </div>
                    </form>
                </div>

                <!-- Recent Members Table Card -->
                <div class="card">
                    <h3 style="font-size: 1.2rem; margin-bottom: 6px;">Anggota Terdaftar Baru-baru Ini</h3>
                    <p class="text-sm text-muted" style="margin-bottom: 16px;">Daftar 10 akun anggota terakhir yang ditambahkan.</p>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                            <thead>
                                <tr style="border-bottom: 1.5px solid var(--border-color); text-align: left; color: var(--text-muted);">
                                    <th style="padding: 10px 8px;">Nama & NIM</th>
                                    <th style="padding: 10px 8px;">Username</th>
                                    <th style="padding: 10px 8px;">Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($members)): ?>
                                    <?php foreach ($members as $m): ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                            <td style="padding: 10px 8px;">
                                                <div style="font-weight: 600; color: var(--dark);"><?= htmlspecialchars($m['nama_lengkap']); ?></div>
                                                <div class="text-xs text-muted"><?= htmlspecialchars($m['nim_npp'] ?: '-'); ?></div>
                                            </td>
                                            <td style="padding: 10px 8px;"><?= htmlspecialchars($m['username']); ?></td>
                                            <td style="padding: 10px 8px;">
                                                <span class="badge badge-primary">Anggota</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 24px; color: var(--text-muted);">Belum ada anggota terdaftar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

<?php else: ?>

    <!-- Public Self-Registration Mode -->
    <div class="register-container">
        <div class="register-hero">
            <a class="brand-public" href="../index.php">
                <span class="brand-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </span>
                <span>Ruang Baca</span>
            </a>

            <div>
                <span class="badge badge-primary" style="background: rgba(255,255,255,0.1); color: #38bdf8; border: 1px solid rgba(255,255,255,0.2); margin-bottom: 16px;">
                    Registrasi Mandiri
                </span>
                <h2 style="font-size: 2.2rem; color: #fff; line-height: 1.2; margin-bottom: 14px;">
                    Satu Akun untuk Semua Koleksi
                </h2>
                <p style="color: #cbd5e1; line-height: 1.6; font-size: 0.95rem;">
                    Daftar sebagai anggota resmi untuk mulai meminjam buku fisik, mengecek riwayat sirkulasi, dan mendapatkan Kartu Anggota Digital.
                </p>
            </div>

            <div style="font-size: 0.8rem; color: #94a3b8;">
                Sudah memiliki akun? <a href="login.php" style="color: #38bdf8; font-weight: 600;">Masuk langsung</a>
            </div>
        </div>

        <div class="register-form-wrap">
            <h3 style="font-size: 1.7rem; margin-bottom: 6px;">Buat Akun Anggota</h3>
            <p class="text-muted text-sm" style="margin-bottom: 24px;">Lengkapi data diri Anda untuk pendaftaran anggota baru.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-input" placeholder="Masukkan nama lengkap Anda" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>" required autocomplete="name">
                </div>

                <div class="form-group">
                    <label class="form-label" for="nim_npp">NIM / NPP / No. Identitas</label>
                    <input type="text" id="nim_npp" name="nim_npp" class="form-input" placeholder="Contoh: 210102003" value="<?= htmlspecialchars($_POST['nim_npp'] ?? ''); ?>" required maxlength="30">
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Username Akun</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Pilih username unik" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" required autocomplete="username">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Min. 6 karakter" required autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="konfirmasi_password">Ulangi Password</label>
                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-input" placeholder="Konfirmasi" required autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px; padding: 12px; font-size: 0.95rem;">
                    Daftar Sebagai Anggota
                </button>
            </form>

            <div style="margin-top: 24px; text-align: center; font-size: 0.85rem; color: var(--text-muted);">
                Sudah punya akun? <a href="login.php" style="font-weight: 600;">Masuk di sini</a> &bull; <a href="../index.php">Kembali ke Beranda</a>
            </div>
        </div>
    </div>

<?php endif; ?>

</body>
</html>
