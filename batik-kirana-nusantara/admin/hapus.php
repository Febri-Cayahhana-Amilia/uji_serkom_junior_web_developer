<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_produk > 0) {
    $stmt = $koneksi->prepare("SELECT gambar FROM produk WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    $produk = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtHapus = $koneksi->prepare("DELETE FROM produk WHERE id_produk = ?");
    $stmtHapus->execute([$id_produk]);

    if ($produk && !empty($produk['gambar'])) {
        $file = __DIR__ . '/../uploads/produk/' . $produk['gambar'];
        if (is_file($file)) @unlink($file);
    }
}

header('Location: dashboard.php');
exit;
