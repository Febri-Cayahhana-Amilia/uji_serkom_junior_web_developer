<?php
/**
 * Skrip sekali-pakai untuk membuat akun admin pertama.
 *
 * WAJIB DIHAPUS dari server setelah dipakai sekali. Selama file ini
 * masih ada di hosting, siapa pun yang tahu URL-nya bisa membukanya —
 * makanya diberi kunci rahasia di bawah supaya tidak jadi celah yang
 * membocorkan status akun admin ke pengunjung acak.
 *
 * Cara pakai: buka
 *   https://domainmu.com/database/setup_admin.php?key=GANTI_KUNCI_INI
 * lalu SEGERA hapus file ini dari server.
 */

$kunci_rahasia = getenv('SETUP_ADMIN_KEY') ?: 'GANTI_KUNCI_INI';

if (!isset($_GET['key']) || !hash_equals($kunci_rahasia, (string) $_GET['key'])) {
    http_response_code(404);
    exit('Not found.');
}

require __DIR__ . '/../includes/db.php';

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$cek = $koneksi->prepare("SELECT id_admin FROM admin WHERE username = ?");
$cek->execute([$username]);

if ($cek->fetch()) {
    echo "Akun admin sudah ada. Tidak ada yang diubah. HAPUS FILE INI SEKARANG.";
} else {
    $stmt = $koneksi->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    if ($stmt->execute([$username, $hash])) {
        echo "Akun admin berhasil dibuat.<br>";
        echo "Username: admin<br>Password: admin123<br>";
        echo "Segera login dan ganti password ini, lalu HAPUS FILE INI dari server.";
    } else {
        echo "Gagal membuat akun admin.";
    }
}