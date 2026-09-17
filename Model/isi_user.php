<?php
require_once __DIR__ . '/config.php';

$hash_password = password_hash('123', PASSWORD_BCRYPT);

$users = [
    [
        'username' => 'admin',
        'password' => $hash_password,
        'nama_lengkap' => 'Petugas Admin',
        'role' => 'admin'
    ],
    [
        'username' => 'Muhamad Abdul Rouf Nasarudin',
        'password' => $hash_password,
        'nama_lengkap' => 'Muhamad Abdul Rouf Nasarudin',
        'role' => 'anggota'
    ],
    [
        'username' => 'Samsul Jonathan',
        'password' => $hash_password,
        'nama_lengkap' => 'Samsul Jonathan',
        'role' => 'kepala'
    ]
];

// Kosongkan dan masukkan data baru
mysqli_query($conn, "TRUNCATE TABLE users");

foreach ($users as $u) {
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $u['username'], $u['password'], $u['nama_lengkap'], $u['role']);
    mysqli_stmt_execute($stmt);
}

echo "Database berhasil dihubungkan dan user berhasil dibuat!";
?>