<?php
/**
 * Koneksi database PostgreSQL — sesuaikan jika konfigurasi kamu berbeda.
 */

$db_host = 'localhost';
$db_port = '5432';
$db_name = 'batik_kirana_nusantara';
$db_user = 'postgres';
$db_pass = 'postgres'; // ganti sesuai password pgAdmin/PostgreSQL kamu

try {
    $koneksi = new PDO(
        "pgsql:host=$db_host;port=$db_port;dbname=$db_name",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage() .
        '<br>Pastikan PostgreSQL aktif dan database "batik_kirana" sudah dibuat dari file database/batik_kirana.sql, dan cek username/password di includes/db.php');
}
