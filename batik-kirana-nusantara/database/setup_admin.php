<?php
require __DIR__ . '/../includes/db.php';

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$cek = $koneksi->prepare("SELECT id_admin FROM admin WHERE username = ?");
$cek->execute([$username]);

if ($cek->fetch()) {
    echo "Akun admin sudah ada. Tidak ada yang diubah.";
} else {
    $stmt = $koneksi->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    if ($stmt->execute([$username, $hash])) {
        echo "Akun admin berhasil dibuat.<br>";
        echo "Username: admin<br>Password: admin123<br>";
        echo "Segera login dan ganti password ini.";
    } else {
        echo "Gagal membuat akun admin.";
    }
}