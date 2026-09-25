<?php

function skema_pembayaran_siap(PDO $koneksi): bool
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['skema_pembayaran'])) {
        return (bool)$_SESSION['skema_pembayaran'];
    }
    try {
        $koneksi->exec("ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS uang_diterima NUMERIC(14,2) DEFAULT NULL");
        $koneksi->exec("ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS kembalian NUMERIC(14,2) DEFAULT NULL");
        $_SESSION['skema_pembayaran'] = true;
    } catch (PDOException $e) {
        error_log('[skema] Gagal menambah kolom pembayaran: ' . $e->getMessage());
        $_SESSION['skema_pembayaran'] = false;
    }
    return (bool)$_SESSION['skema_pembayaran'];
}
