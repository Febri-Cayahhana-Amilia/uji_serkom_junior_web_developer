<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$produkList = $koneksi->query(
    "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar, k.nama_kategori
     FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori
     ORDER BY p.id_produk DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — Batik Kirana Nusantara</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-shell">

<div class="admin-topbar">
  <div class="wrap">
    <span>Masuk sebagai <b><?= htmlspecialchars($_SESSION['admin_username']) ?></b></span>
    <a href="logout.php">Keluar</a>
  </div>
</div>

<div class="wrap" style="padding-top:40px; padding-bottom:60px;">
  <div class="admin-toolbar">
    <h2 class="section-title" style="margin:0;">Kelola Produk</h2>
    <a href="tambah.php" class="btn btn-gold">+ Tambah Produk</a>
  </div>

  <table class="admin-table">
    <thead>
      <tr>
        <th>Foto</th>
        <th>Nama Produk</th>
        <th>Kategori</th>
        <th>Harga</th>
        <th>Stok</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($produkList) > 0): ?>
        <?php foreach ($produkList as $row): ?>
          <tr>
            <td>
              <?php if (!empty($row['gambar']) && is_file(__DIR__ . '/../uploads/produk/' . $row['gambar'])): ?>
                <img src="../uploads/produk/<?= htmlspecialchars($row['gambar']) ?>" alt=""
                     style="width:48px; height:48px; object-fit:cover; border-radius:6px; display:block;">
              <?php else: ?>
                <span style="font-size:12px; color:#A3402C;">Belum ada foto</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['nama_produk']) ?></td>
            <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
            <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
            <td><?= (int)$row['stok'] ?></td>
            <td class="admin-actions">
              <a href="edit.php?id=<?= (int)$row['id_produk'] ?>" class="link-edit">Edit</a>
              <a href="hapus.php?id=<?= (int)$row['id_produk'] ?>" class="link-hapus"
                 onclick="return confirm('Yakin ingin menghapus produk ini?');">Hapus</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6">Belum ada produk.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
