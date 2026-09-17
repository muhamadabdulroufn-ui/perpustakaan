<?php
session_start();
require_once __DIR__ . '/../Model/config.php';

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['id_user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = isset($_GET['registered']) ? 'Pendaftaran berhasil! Silakan masuk dengan akun baru Anda.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id_user, username, password, nama_lengkap, role FROM users WHERE username = ? LIMIT 1');

        if (!$stmt) {
            $error = 'Gagal memproses login. Silakan coba beberapa saat lagi.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['id_user']      = $user['id_user'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role']         = $user['role'];

                header('Location: dashboard.php');
                exit;
            }

            $error = 'Username atau password yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Sistem - Ruang Baca Perpustakaan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body.auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: radial-gradient(circle at 10% 20%, rgba(79, 70, 229, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(6, 182, 212, 0.15) 0%, transparent 40%),
                        #0f172a;
        }
        .auth-container {
            width: 100%;
            max-width: 1020px;
            background: #ffffff;
            border-radius: var(--radius-xl);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.5);
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .auth-hero-side {
            background: linear-gradient(145deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            color: #ffffff;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .auth-hero-side::before {
            content: '';
            position: absolute;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.25) 0%, transparent 70%);
            top: -60px;
            right: -60px;
            pointer-events: none;
        }
        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
        }
        .auth-brand-badge {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            border-radius: 12px;
            display: grid;
            place-items: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
        }
        .hero-statement h2 {
            font-size: 2.2rem;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 16px;
        }
        .hero-statement p {
            color: #cbd5e1;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .auth-form-side {
            padding: 48px clamp(28px, 6vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth-form-side h3 {
            font-size: 1.75rem;
            margin-bottom: 6px;
            color: var(--dark);
        }
        .auth-subhead {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 28px;
        }
        .password-input-wrap {
            position: relative;
        }
        .btn-toggle-eye {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            display: grid;
            place-items: center;
            transition: color 0.2s;
        }
        .btn-toggle-eye:hover {
            color: var(--primary);
        }
        /* Demo account quick switcher */
        .demo-roles-box {
            margin-top: 24px;
            padding: 16px;
            background: var(--bg-page);
            border-radius: var(--radius-md);
            border: 1px dashed var(--border-color);
        }
        .demo-roles-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .demo-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .demo-pill {
            background: #ffffff;
            border: 1px solid var(--border-color);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            color: var(--text-main);
            transition: var(--transition-fast);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .demo-pill:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        .demo-pill-admin { border-left: 3px solid #4f46e5; }
        .demo-pill-kepala { border-left: 3px solid #10b981; }
        .demo-pill-anggota { border-left: 3px solid #06b6d4; }

        @media (max-width: 820px) {
            .auth-container { grid-template-columns: 1fr; }
            .auth-hero-side { padding: 32px 28px; }
            .auth-form-side { padding: 36px 24px; }
        }
    </style>
</head>
<body class="auth-page">

    <div class="auth-container">
        <!-- Left Showcase Side -->
        <div class="auth-hero-side">
            <a class="auth-brand" href="../index.php">
                <span class="auth-brand-badge">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </span>
                <span>Ruang Baca</span>
            </a>

            <div class="hero-statement">
                <h2>Kelola & Jelajahi Literasi Tanpa Batas</h2>
                <p>
                    Portal terpadu untuk Petugas Perpustakaan, Anggota Pembaca, dan Manajemen Kepala Perpustakaan.
                </p>
            </div>

            <div style="font-size: 0.8rem; color: #94a3b8; display: flex; align-items: center; gap: 8px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981;"></span>
                Sirkulasi & Database Terkoneksi Real-time
            </div>
        </div>

        <!-- Right Form Side -->
        <div class="auth-form-side">
            <h3>Selamat Datang</h3>
            <p class="auth-subhead">Masukkan username dan password akun Anda untuk melanjutkan.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span><?= htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username" required autocomplete="username" autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" required autocomplete="current-password" style="padding-right: 48px;">
                        <button type="button" class="btn-toggle-eye" id="togglePasswordBtn" title="Tampilkan password" aria-label="Tampilkan password">
                            <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 10px; font-size: 0.95rem;">
                    Masuk ke Sistem
                </button>
            </form>

            <!-- Quick-Fill Demo Switcher -->
            <div class="demo-roles-box">
                <div class="demo-roles-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    Coba Akun Demo (1-Klik Isi):
                </div>
                <div class="demo-pills">
                    <button type="button" class="demo-pill demo-pill-admin" onclick="fillCredentials('admin', '123')">
                        <strong>Admin</strong> (Petugas)
                    </button>
                    <button type="button" class="demo-pill demo-pill-kepala" onclick="fillCredentials('Samsul Jonathan', '123')">
                        <strong>Kepala</strong> (Pimpinan)
                    </button>
                    <button type="button" class="demo-pill demo-pill-anggota" onclick="fillCredentials('Muhamad Abdul Rouf Nasarudin', '123')">
                        <strong>Anggota</strong> (Mahasiswa)
                    </button>
                </div>
            </div>

            <div style="margin-top: 24px; text-align: center; font-size: 0.85rem; color: var(--text-muted);">
                Belum punya akun anggota? <a href="register.php" style="font-weight: 600;">Daftar Mandiri di sini</a> &bull; <a href="../index.php">Kembali ke Beranda</a>
            </div>
        </div>
    </div>

    <script>
        function fillCredentials(user, pass) {
            document.getElementById('username').value = user;
            document.getElementById('password').value = pass;
            document.getElementById('password').focus();
        }

        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const eyeIcon = document.getElementById('eyeIcon');

        toggleBtn.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            
            if (isPassword) {
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        });
    </script>
</body>
</html>