<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../Model/config.php';

$id_peminjaman = (int)($_GET['id'] ?? 0);
$id_buku       = (int)($_GET['id_buku'] ?? 0);
$tgl_kembali   = date('Y-m-d');
$ref           = $_GET['ref'] ?? 'laporan';

if ($id_peminjaman > 0 && $id_buku > 0) {
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "UPDATE peminjaman SET status = 'kembali', tanggal_kembali = '$tgl_kembali' WHERE id_peminjaman = $id_peminjaman");
        mysqli_query($conn, "UPDATE buku SET stok = stok + 1 WHERE id_buku = $id_buku");
        mysqli_commit($conn);
        $pesan = "Pengembalian buku berhasil diproses dan stok telah diperbarui!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $pesan = "Terjadi kesalahan saat memproses pengembalian buku.";
    }
} else {
    $pesan = "Data peminjaman tidak valid.";
}

if ($ref === 'dashboard') {
    header("Location: dashboard.php?pesan=" . urlencode($pesan));
} else {
    header("Location: export_laporan.php?pesan=" . urlencode($pesan));
}
exit;
?>