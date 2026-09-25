<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo 'Driver pgsql: ' . (extension_loaded('pdo_pgsql') ? 'AKTIF' : 'TIDAK AKTIF') . '<br>';
$h = getenv('DB_HOST') ?: 'altaria.proxy.rlwy.net';
$p = getenv('DB_PORT') ?: '35741';
echo 'Menyambung ke ' . $h . ':' . $p . '<br>';
try {
    $pesan = file_get_contents(__DIR__ . '/includes/db.php');
    preg_match("/DB_PASS'\) \?: '([^']*)'/", $pesan, $m);
    new PDO("pgsql:host=$h;port=$p;dbname=railway", 'postgres', getenv('DB_PASS') ?: ($m[1] ?? ''));
    echo 'Koneksi BERHASIL';
} catch (Throwable $e) {
    echo 'Gagal: ' . htmlspecialchars($e->getMessage());
}