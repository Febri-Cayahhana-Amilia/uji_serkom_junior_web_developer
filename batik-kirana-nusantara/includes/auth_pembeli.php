<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Ambil data pembeli yang sedang login, atau null kalau belum login.
 * Dipakai di header (nama & link akun) dan checkout (isi otomatis).
 */
function pembeli_saat_ini(PDO $koneksi): ?array {
    if (empty($_SESSION['pembeli_id'])) {
        return null;
    }
    $stmt = $koneksi->prepare(
        "SELECT id_pembeli, nama_pembeli, email, telepon, alamat FROM pembeli WHERE id_pembeli = ?"
    );
    $stmt->execute([$_SESSION['pembeli_id']]);
    $pembeli = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pembeli) {
        // Akun sudah dihapus tapi sesi masih ada — bersihkan.
        unset($_SESSION['pembeli_id'], $_SESSION['pembeli_nama']);
        return null;
    }
    return $pembeli;
}
