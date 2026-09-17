<?php
/**
 * Koneksi database PostgreSQL.
 *
 * PENTING (keamanan): kredensial di bawah ini SEBAIKNYA diisi lewat
 * environment variable di hosting (DB_HOST, DB_PORT, DB_NAME, DB_USER,
 * DB_PASS), bukan ditulis langsung di file ini. Nilai hardcode di bawah
 * hanya fallback supaya project tetap jalan kalau env var belum diatur.
 *
 * Karena password di bawah sempat tertulis polos di source code,
 * SEGERA GANTI password database ini dari dashboard Railway lalu
 * update juga nilainya di sini / di environment variable hosting.
 */

$db_host = getenv('DB_HOST') ?: 'altaria.proxy.rlwy.net';
$db_port = getenv('DB_PORT') ?: '35741';
$db_name = getenv('DB_NAME') ?: 'railway';
$db_user = getenv('DB_USER') ?: 'postgres';
$db_pass = getenv('DB_PASS') ?: 'cBUFYmbFZEzbZiaaHdtmWYDfCDNbqTYT'; // ganti setelah rotate password di Railway

try {
    $koneksi = new PDO(
        "pgsql:host=$db_host;port=$db_port;dbname=$db_name",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('[db] Koneksi database gagal: ' . $e->getMessage());
    die('Situs sedang bermasalah menghubungkan ke database. Silakan coba lagi beberapa saat lagi.');
}

