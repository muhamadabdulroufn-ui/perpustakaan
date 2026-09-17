<?php
session_start();
require_once __DIR__ . '/Model/config.php';

$keyword = trim((string)($_GET['cari'] ?? ''));
$safe_keyword = mysqli_real_escape_string($conn, $keyword);
$query = "SELECT id_buku, judul, penulis, penerbit, tahun_terbit, stok, cover_path FROM buku WHERE judul LIKE '%$safe_keyword%' OR penulis LIKE '%$safe_keyword%' ORDER BY judul ASC";
$result = mysqli_query($conn, $query);

$books = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $books[] = $row;
    }
}

$total_buku = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM buku'))['total'] ?? 0;
$total_tersedia = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM buku WHERE stok > 0'))['total'] ?? 0;
$total_anggota = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'anggota'"))['total'] ?? 0;
$logged_in = isset($_SESSION['id_user']);
$user_name = $_SESSION['nama_lengkap'] ?? '';
$user_role = $_SESSION['role'] ?? '';

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
    <title>Ruang Baca - Sistem Perpustakaan Modern</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Landing Page Specific Styling */
        .navbar-public {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 16px 28px;
        }
        .navbar-inner {
            max-width: 1240px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand-public {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
        }
        .brand-badge {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: grid;
            place-items: center;
            color: #fff;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
        }
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-item {
            color: #cbd5e1;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition-fast);
        }
        .nav-item:hover {
            color: #ffffff;
        }
        .hero-public {
            background: radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 20% 80%, rgba(79, 70, 229, 0.2) 0%, transparent 50%),
                        linear-gradient(180deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            color: #ffffff;
            padding: 90px 24px 130px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .hero-container {
            max-width: 860px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 6px 16px;
            border-radius: var(--radius-full);
            color: #38bdf8;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 24px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .hero-title {
            font-size: clamp(2.3rem, 5vw, 4rem);
            line-height: 1.15;
            color: #ffffff;
            margin-bottom: 20px;
            font-weight: 800;
        }
        .hero-title span {
            background: linear-gradient(135deg, #38bdf8, #818cf8, #c084fc);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-desc {
            font-size: 1.125rem;
            color: #94a3b8;
            max-width: 640px;
            margin: 0 auto 36px;
            line-height: 1.7;
        }
        .hero-search-wrap {
            max-width: 620px;
            margin: 0 auto;
            position: relative;
        }
        .hero-search-input {
            width: 100%;
            height: 60px;
            padding: 0 140px 0 54px;
            border-radius: var(--radius-full);
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            font-size: 1rem;
            color: var(--text-main);
            outline: none;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.35);
            transition: var(--transition-normal);
        }
        .hero-search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 5px rgba(79, 70, 229, 0.4), 0 16px 36px rgba(0, 0, 0, 0.35);
        }
        .hero-search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        .hero-search-btn {
            position: absolute;
            right: 8px;
            top: 8px;
            bottom: 8px;
            padding: 0 24px;
            border-radius: var(--radius-full);
        }
        .stats-strip {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 54px;
            flex-wrap: wrap;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: var(--radius-lg);
            backdrop-filter: blur(4px);
        }
        .stat-num {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: #ffffff;
        }
        .stat-text {
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: left;
            line-height: 1.3;
        }

        /* Catalog Section */
        .catalog-section {
            max-width: 1240px;
            margin: -60px auto 80px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }
        .catalog-card-wrapper {
            background: #ffffff;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-xl);
            padding: 32px;
        }
        .catalog-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .filter-tabs {
            display: flex;
            gap: 8px;
            background: var(--bg-page);
            padding: 4px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }
        .tab-btn {
            border: none;
            background: transparent;
            padding: 8px 16px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition-fast);
        }
        .tab-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        /* Books Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 22px;
            margin-top: 24px;
        }
        .book-item-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: var(--transition-normal);
            position: relative;
        }
        .book-item-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: #cbd5e1;
        }
        .book-thumb-wrap {
            position: relative;
            height: 200px;
            background: #e2e8f0;
            overflow: hidden;
        }
        .book-thumb {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .book-item-card:hover .book-thumb {
            transform: scale(1.05);
        }
        .stock-pill-floating {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
        }
        .book-content {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
            justify-content: space-between;
        }
        .book-title-main {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .book-author-text {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 14px;
        }
        .book-footer {
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .book-publisher-info {
            font-size: 0.78rem;
            color: var(--text-light);
        }

        /* Footer */
        .footer-public {
            background: #0f172a;
            color: #94a3b8;
            padding: 60px 24px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.875rem;
        }
        .footer-inner {
            max-width: 1240px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-bottom {
            max-width: 1240px;
            margin: 0 auto;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
        }

        /* Reference-inspired public landing composition */
        body:has(.hero-public) {
            background: #e8e9eb;
        }

        .navbar-public {
            position: relative;
            max-width: 1240px;
            margin: 32px auto 0;
            padding: 12px 28px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #e0e3e7;
            border-radius: 16px 16px 0 0;
            box-shadow: 0 10px 30px rgba(31, 41, 55, 0.06);
        }

        .brand-public {
            color: #101828;
            font-size: 1.05rem;
            letter-spacing: -0.02em;
        }

        .brand-badge {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #101828;
            box-shadow: none;
        }

        .brand-badge svg {
            width: 16px;
            height: 16px;
        }

        .nav-menu {
            gap: 26px;
        }

        .nav-item {
            color: #475467;
            font-size: 0.78rem;
        }

        .nav-item:hover {
            color: #101828;
        }

        .navbar-public .btn-secondary,
        .navbar-public .btn-primary,
        .navbar-public .btn-danger {
            padding: 7px 13px;
            font-size: 0.75rem;
            border-radius: 9px;
            box-shadow: none;
        }

        .hero-public {
            max-width: 1240px;
            min-height: 560px;
            margin: 0 auto;
            padding: 86px 24px 74px;
            border: 1px solid #dfe2e6;
            border-radius: 0 0 24px 24px;
            background-color: #fbfbfa;
            background-image: radial-gradient(#d6d8dc 0.7px, transparent 0.7px);
            background-size: 7px 7px;
            color: #101828;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(31, 41, 55, 0.07);
        }

        .hero-public::before,
        .hero-public::after {
            content: '';
            position: absolute;
            width: 360px;
            height: 360px;
            border: 1px solid rgba(152, 162, 179, 0.18);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-public::before {
            left: -240px;
            top: 100px;
        }

        .hero-public::after {
            right: -240px;
            bottom: 40px;
        }

        .hero-container {
            max-width: 720px;
        }

        .hero-pill {
            margin-bottom: 20px;
            padding: 6px 12px;
            border: 1px solid #d0d5dd;
            background: rgba(255, 255, 255, 0.78);
            color: #344054;
            font-size: 0.72rem;
            text-transform: none;
        }

        .hero-pill span {
            color: #1570ef;
        }

        .hero-title {
            color: #101828;
            font-size: clamp(2.8rem, 6vw, 4.9rem);
            line-height: 1.05;
            letter-spacing: -0.055em;
            margin-bottom: 22px;
        }

        .hero-title span {
            background: none;
            color: #98a2b3;
            -webkit-text-fill-color: initial;
        }

        .hero-desc {
            max-width: 480px;
            margin-bottom: 28px;
            color: #475467;
            font-size: 0.92rem;
            line-height: 1.65;
        }

        .hero-search-wrap {
            max-width: 420px;
            margin: 0 auto;
        }

        .hero-search-input {
            height: 48px;
            padding: 0 108px 0 42px;
            border: 1px solid #d0d5dd;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 18px rgba(31, 41, 55, 0.08);
            font-size: 0.82rem;
        }

        .hero-search-icon {
            left: 15px;
        }

        .hero-search-btn {
            right: 5px;
            top: 5px;
            bottom: 5px;
            padding: 0 15px;
            border-radius: 8px;
            font-size: 0.76rem;
        }

        .stats-strip {
            gap: 10px;
            margin-top: 28px;
        }

        .stat-item {
            padding: 7px 12px;
            border: 1px solid #e4e7ec;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.7);
        }

        .stat-num {
            color: #101828;
            font-size: 1.15rem;
        }

        .stat-text {
            color: #667085;
            font-size: 0.66rem;
        }

        .hero-decoration {
            position: absolute;
            z-index: 1;
            color: #344054;
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(208, 213, 221, 0.9);
            box-shadow: 0 12px 22px rgba(31, 41, 55, 0.1);
            backdrop-filter: blur(5px);
        }

        .hero-note {
            width: 156px;
            padding: 18px 15px 16px;
            left: 42px;
            top: 45px;
            transform: rotate(-5deg);
            background: #fff4b8;
            border-color: #f1df85;
            font-family: 'Space Grotesk', sans-serif;
        }

        .hero-note .pin-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            top: -5px;
            left: 50%;
            border-radius: 50%;
            background: #e11d48;
        }

        .hero-note strong,
        .hero-note p {
            display: block;
        }

        .hero-note strong {
            margin-bottom: 8px;
            font-size: 0.76rem;
        }

        .hero-note p {
            color: #667085;
            font-size: 0.67rem;
            line-height: 1.45;
        }

        .hero-stack {
            width: 178px;
            right: 42px;
            top: 42px;
            padding: 16px;
            transform: rotate(5deg);
        }

        .stack-icon {
            display: inline-block;
            width: 30px;
            height: 30px;
            margin: -2px 3px 13px 0;
            border-radius: 8px;
            vertical-align: top;
            box-shadow: 0 5px 8px rgba(31, 41, 55, 0.12);
        }

        .stack-icon-blue { background: #60a5fa; }
        .stack-icon-yellow { background: #facc15; }
        .stack-icon-red { background: #fb7185; }

        .hero-stack strong,
        .hero-stack p,
        .hero-reminder strong,
        .hero-reminder span,
        .hero-reminder b,
        .hero-check small {
            display: block;
        }

        .hero-stack strong,
        .hero-reminder strong {
            font-size: 0.75rem;
        }

        .hero-stack p {
            margin-top: 4px;
            color: #667085;
            font-size: 0.63rem;
            line-height: 1.4;
        }

        .hero-check {
            display: flex;
            align-items: center;
            gap: 10px;
            left: 55px;
            bottom: 38px;
            padding: 11px 14px;
            transform: rotate(4deg);
        }

        .check-mark {
            display: grid;
            width: 28px;
            height: 28px;
            place-items: center;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-size: 1rem;
        }

        .hero-check strong,
        .hero-check small {
            font-size: 0.65rem;
        }

        .hero-check small {
            margin-top: 2px;
            color: #667085;
        }

        .hero-reminder {
            width: 154px;
            right: 54px;
            bottom: 40px;
            padding: 15px;
            transform: rotate(-4deg);
        }

        .hero-reminder span {
            margin-top: 9px;
            color: #667085;
            font-size: 0.62rem;
        }

        .hero-reminder b {
            margin-top: 6px;
            color: #1570ef;
            font-size: 0.68rem;
        }

        @media (max-width: 768px) {
            .navbar-public { margin: 12px 12px 0; border-radius: 14px 14px 0 0; }
            .navbar-inner { flex-direction: column; gap: 14px; }
            .nav-menu { gap: 10px; flex-wrap: wrap; justify-content: center; }
            .nav-item { display: none; }
            .hero-public { margin: 0 12px; min-height: 650px; padding: 150px 18px 100px; }
            .hero-title { font-size: 2.2rem; }
            .hero-search-input { height: 52px; padding-right: 110px; font-size: 0.9rem; }
            .hero-search-btn { padding: 0 16px; font-size: 0.85rem; }
            .stats-strip { gap: 16px; }
            .hero-note { left: 18px; top: 24px; transform: rotate(-4deg) scale(0.82); transform-origin: top left; }
            .hero-stack { right: 18px; top: 24px; transform: rotate(4deg) scale(0.82); transform-origin: top right; }
            .hero-check { left: 24px; bottom: 24px; transform: rotate(3deg) scale(0.85); transform-origin: bottom left; }
            .hero-reminder { right: 24px; bottom: 24px; transform: rotate(-3deg) scale(0.8); transform-origin: bottom right; }
            .footer-inner { grid-template-columns: 1fr; gap: 24px; }
        }

        /* Full-viewport presentation on desktop */
        @media (min-width: 769px) {
            body:has(.hero-public) {
                background: #fbfbfa;
            }

            .navbar-public {
                max-width: none;
                margin: 0;
                padding: 14px clamp(28px, 7vw, 110px);
                border: 0;
                border-bottom: 1px solid #e4e7ec;
                border-radius: 0;
                box-shadow: 0 4px 18px rgba(31, 41, 55, 0.04);
            }

            .hero-public {
                max-width: none;
                min-height: calc(100vh - 68px);
                margin: 0;
                padding: clamp(78px, 11vh, 132px) 24px clamp(68px, 9vh, 110px);
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .hero-container {
                max-width: 840px;
            }

            .hero-title {
                font-size: clamp(3.4rem, 6.2vw, 6.4rem);
            }

            .hero-desc {
                max-width: 560px;
                font-size: 1rem;
            }

            .hero-note {
                left: clamp(46px, 10vw, 180px);
                top: clamp(45px, 8vh, 90px);
            }

            .hero-stack {
                right: clamp(46px, 10vw, 180px);
                top: clamp(42px, 8vh, 90px);
            }

            .hero-check {
                left: clamp(55px, 12vw, 220px);
                bottom: clamp(30px, 7vh, 74px);
            }

            .hero-reminder {
                right: clamp(54px, 12vw, 220px);
                bottom: clamp(32px, 7vh, 74px);
            }
        }
    </style>
</head>
<body>

    <!-- Public Navigation Bar -->
    <header class="navbar-public">
        <div class="navbar-inner">
            <a class="brand-public" href="index.php">
                <span class="brand-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </span>
                <span>Ruang Baca</span>
            </a>

            <nav class="nav-menu">
                <a class="nav-item" href="#katalog">Katalog Buku</a>
                <a class="nav-item" href="View/katalog.php">Semua Koleksi</a>
                <?php if ($logged_in): ?>
                    <a class="btn btn-secondary btn-sm" href="Controller/dashboard.php">
                        Dashboard (<?= htmlspecialchars($user_name); ?>)
                    </a>
                    <a class="btn btn-danger btn-sm" href="Controller/logout.php">Keluar</a>
                <?php else: ?>
                    <a class="btn btn-secondary btn-sm" href="Controller/register.php">Daftar Anggota</a>
                    <a class="btn btn-primary btn-sm" href="Controller/login.php">Masuk ke Sistem</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-public">
        <div class="hero-decoration hero-note hero-decoration-left">
            <span class="pin-dot"></span>
            <strong>Catatan membaca</strong>
            <p>Temukan cerita baru, satu halaman setiap hari.</p>
        </div>
        <div class="hero-decoration hero-stack hero-decoration-right">
            <span class="stack-icon stack-icon-blue"></span>
            <span class="stack-icon stack-icon-yellow"></span>
            <span class="stack-icon stack-icon-red"></span>
            <strong>Koleksi pilihan</strong>
            <p>Ragam buku untuk setiap rasa ingin tahu.</p>
        </div>
        <div class="hero-decoration hero-check hero-decoration-bottom-left">
            <span class="check-mark">&#10003;</span>
            <span><strong>Ruang baca siap</strong><small>untuk perjalanan ide Anda</small></span>
        </div>
        <div class="hero-decoration hero-reminder hero-decoration-bottom-right">
            <strong>Pengingat</strong>
            <span>Jatuh tempo peminjaman</span>
            <b>7 hari kalender</b>
        </div>
        <div class="hero-container">
            <div class="hero-pill">
                <span>✦</span> Perpustakaan digital kampus
            </div>
            <h1 class="hero-title">
                Temukan bacaan baru,<br><span>mulai dari sini</span>
            </h1>
            <p class="hero-desc">
                Jelajahi koleksi perpustakaan, cek ketersediaan buku, dan kelola perjalanan membaca Anda dalam satu ruang yang sederhana.
            </p>

            <!-- Instant Search Input -->
            <div class="hero-search-wrap">
                <span class="hero-search-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" id="liveSearchInput" class="hero-search-input" placeholder="Ketik judul buku, penulis, atau topik..." value="<?= htmlspecialchars($keyword); ?>" autocomplete="off">
                <button type="button" id="btnSearchAction" class="btn btn-primary hero-search-btn">Cari Buku</button>
            </div>

            <!-- Stats Bar -->
            <div class="stats-strip">
                <div class="stat-item">
                    <span class="stat-num"><?= $total_buku; ?></span>
                    <span class="stat-text">Judul Koleksi<br>Tersedia</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num"><?= $total_tersedia; ?></span>
                    <span class="stat-text">Buku Siap<br>Dipinjam</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num"><?= $total_anggota; ?></span>
                    <span class="stat-text">Anggota Aktif<br>Terdaftar</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Catalog Section -->
    <main class="catalog-section" id="katalog">
        <div class="catalog-card-wrapper">
            <div class="catalog-header">
                <div>
                    <h2>Katalog Koleksi Terbaru</h2>
                    <p class="text-muted text-sm">Lihat ketersediaan buku secara langsung. Klik kartu untuk melihat detail lengkap.</p>
                </div>
                <div class="filter-tabs">
                    <button class="tab-btn active" data-filter="all">Semua Buku (<?= count($books); ?>)</button>
                    <button class="tab-btn" data-filter="available">Tersedia Saja</button>
                    <button class="tab-btn" data-filter="borrowed">Sedang Dipinjam</button>
                </div>
            </div>

            <!-- Book Grid Container -->
            <div class="books-grid" id="booksGrid">
                <?php if (!empty($books)): ?>
                    <?php foreach ($books as $index => $book): ?>
                        <?php 
                            $cover_url = !empty($book['cover_path']) ? $book['cover_path'] : $cover_images[(int)$book['id_buku'] % count($cover_images)];
                            $is_available = (int)$book['stok'] > 0;
                        ?>
                        <article class="book-item-card" 
                                 data-id="<?= $book['id_buku']; ?>"
                                 data-title="<?= htmlspecialchars($book['judul']); ?>"
                                 data-author="<?= htmlspecialchars($book['penulis']); ?>"
                                 data-publisher="<?= htmlspecialchars($book['penerbit']); ?>"
                                 data-year="<?= $book['tahun_terbit']; ?>"
                                 data-stock="<?= $book['stok']; ?>"
                                 data-cover="<?= htmlspecialchars($cover_url); ?>"
                                 data-status="<?= $is_available ? 'available' : 'borrowed'; ?>">
                            <div class="book-thumb-wrap">
                                <img class="book-thumb" src="<?= htmlspecialchars($cover_url); ?>" alt="<?= htmlspecialchars($book['judul']); ?>" loading="lazy">
                                <div class="stock-pill-floating">
                                    <?php if ($is_available): ?>
                                        <span class="badge badge-success"><span class="badge-dot"></span> <?= $book['stok']; ?> Eks. Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><span class="badge-dot"></span> Dipinjam Semua</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="book-content">
                                <div>
                                    <h3 class="book-title-main" title="<?= htmlspecialchars($book['judul']); ?>"><?= htmlspecialchars($book['judul']); ?></h3>
                                    <p class="book-author-text">oleh <strong><?= htmlspecialchars($book['penulis']); ?></strong></p>
                                </div>
                                <div class="book-footer">
                                    <span class="book-publisher-info"><?= htmlspecialchars($book['penerbit']); ?> &bull; <?= $book['tahun_terbit']; ?></span>
                                    <button type="button" class="btn btn-secondary btn-sm btn-detail-trigger">Detail</button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--text-muted);">
                        <p style="font-size: 1.1rem; font-weight: 600;">Belum ada buku yang ditemukan.</p>
                        <p class="text-sm">Silakan gunakan kata kunci pencarian yang lain.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Empty Search State -->
            <div id="noResultsNotice" style="display: none; text-align: center; padding: 48px; color: var(--text-muted);">
                <p style="font-size: 1.1rem; font-weight: 600;">Tidak ada buku yang cocok dengan pencarian Anda.</p>
                <p class="text-sm">Coba cari dengan judul lain atau kata kunci penulis yang berbeda.</p>
            </div>
        </div>
    </main>

    <!-- Book Detail Modal -->
    <div class="modal-backdrop" id="bookModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalBookTitle">Detail Informasi Buku</h3>
                <button type="button" class="modal-close" id="modalCloseBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 140px 1fr; gap: 20px;">
                    <img id="modalCover" src="" alt="Cover" style="width: 100%; height: 200px; object-fit: cover; border-radius: var(--radius-md); box-shadow: var(--shadow-md);">
                    <div>
                        <div id="modalStockBadge" style="margin-bottom: 10px;"></div>
                        <h4 id="modalHeading" style="font-size: 1.25rem; margin-bottom: 6px;">-</h4>
                        <p id="modalAuthor" class="text-muted text-sm" style="margin-bottom: 16px;">-</p>
                        
                        <div style="background: var(--bg-page); padding: 12px; border-radius: var(--radius-md); font-size: 0.85rem; display: grid; gap: 6px;">
                            <div><strong>Penerbit:</strong> <span id="modalPublisher">-</span></div>
                            <div><strong>Tahun Terbit:</strong> <span id="modalYear">-</span></div>
                            <div><strong>Sisa Stok Fisik:</strong> <span id="modalStock">-</span> eksemplar</div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <h5 style="font-size: 0.95rem; margin-bottom: 6px;">Informasi Peminjaman</h5>
                    <p class="text-muted text-sm" style="line-height: 1.6;">
                        Buku ini dapat dipinjam oleh seluruh anggota perpustakaan yang aktif dengan durasi peminjaman standar selama 7 (tujuh) hari kalender.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" id="modalDismissBtn">Tutup</button>
                <?php if ($logged_in): ?>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="Controller/pinjam.php" class="btn btn-primary btn-sm">Catat Peminjaman Ini</a>
                    <?php else: ?>
                        <a href="Controller/dashboard.php" class="btn btn-primary btn-sm">Lihat di Dashboard Saya</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="Controller/login.php" class="btn btn-primary btn-sm">Masuk untuk Meminjam</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Public Footer -->
    <footer class="footer-public">
        <div class="footer-inner">
            <div>
                <h4 style="color: #fff; margin-bottom: 12px;">Ruang Baca Digital</h4>
                <p style="line-height: 1.7;">
                    Platform sirkulasi dan katalog perpustakaan modern yang mempermudah pengelolaan koleksi buku, pencatatan peminjaman, serta pelaporan terpusat.
                </p>
            </div>
            <div>
                <h5 style="color: #fff; margin-bottom: 12px;">Jam Layanan Perpustakaan</h5>
                <p>Senin - Jumat: 08.00 - 16.00 WIB</p>
                <p>Sabtu: 08.30 - 13.00 WIB</p>
                <p>Minggu & Libur Nasional: Tutup</p>
            </div>
            <div>
                <h5 style="color: #fff; margin-bottom: 12px;">Akses Cepat Pengguna</h5>
                <p><a href="Controller/login.php" style="color: #cbd5e1;">Portal Masuk Petugas & Anggota</a></p>
                <p><a href="Controller/register.php" style="color: #cbd5e1;">Pendaftaran Anggota Baru</a></p>
                <p><a href="View/katalog.php" style="color: #cbd5e1;">Katalog Buku Lengkap</a></p>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y'); ?> Ruang Baca Perpustakaan. Seluruh hak cipta dilindungi.
        </div>
    </footer>

    <!-- Interactive JavaScript -->
    <script>
    (function() {
        const liveSearchInput = document.getElementById('liveSearchInput');
        const booksGrid = document.getElementById('booksGrid');
        const bookCards = Array.from(document.querySelectorAll('.book-item-card'));
        const noResultsNotice = document.getElementById('noResultsNotice');
        const tabBtns = document.querySelectorAll('.tab-btn');
        let currentFilter = 'all';

        // Modal Elements
        const bookModal = document.getElementById('bookModal');
        const modalCloseBtn = document.getElementById('modalCloseBtn');
        const modalDismissBtn = document.getElementById('modalDismissBtn');
        const modalHeading = document.getElementById('modalHeading');
        const modalAuthor = document.getElementById('modalAuthor');
        const modalPublisher = document.getElementById('modalPublisher');
        const modalYear = document.getElementById('modalYear');
        const modalStock = document.getElementById('modalStock');
        const modalCover = document.getElementById('modalCover');
        const modalStockBadge = document.getElementById('modalStockBadge');

        function filterBooks() {
            const query = liveSearchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            bookCards.forEach(card => {
                const title = (card.dataset.title || '').toLowerCase();
                const author = (card.dataset.author || '').toLowerCase();
                const status = card.dataset.status;

                const matchesQuery = !query || title.includes(query) || author.includes(query);
                const matchesFilter = (currentFilter === 'all') || (status === currentFilter);

                if (matchesQuery && matchesFilter) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            noResultsNotice.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        if (liveSearchInput) {
            liveSearchInput.addEventListener('input', filterBooks);
        }

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.filter;
                filterBooks();
            });
        });

        // Detail Modal Interactions
        function openBookModal(card) {
            const title = card.dataset.title;
            const author = card.dataset.author;
            const publisher = card.dataset.publisher;
            const year = card.dataset.year;
            const stock = parseInt(card.dataset.stock, 10);
            const cover = card.dataset.cover;

            modalHeading.textContent = title;
            modalAuthor.textContent = `Ditulis oleh ${author}`;
            modalPublisher.textContent = publisher;
            modalYear.textContent = year;
            modalStock.textContent = stock;
            modalCover.src = cover;

            if (stock > 0) {
                modalStockBadge.innerHTML = `<span class="badge badge-success"><span class="badge-dot"></span> Tersedia (${stock} eksemplar)</span>`;
            } else {
                modalStockBadge.innerHTML = `<span class="badge badge-danger"><span class="badge-dot"></span> Stok Habis / Dipinjam</span>`;
            }

            bookModal.classList.add('active');
        }

        function closeModal() {
            bookModal.classList.remove('active');
        }

        document.querySelectorAll('.btn-detail-trigger').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const card = btn.closest('.book-item-card');
                openBookModal(card);
            });
        });

        bookCards.forEach(card => {
            card.addEventListener('click', () => openBookModal(card));
        });

        if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
        if (modalDismissBtn) modalDismissBtn.addEventListener('click', closeModal);
        if (bookModal) {
            bookModal.addEventListener('click', (e) => {
                if (e.target === bookModal) closeModal();
            });
        }
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
    })();
    </script>
</body>
</html>
