<?php

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

