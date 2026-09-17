<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

$stmt = $koneksi->prepare(
    "SELECT * FROM pesanan
     WHERE dibuat_pada::date BETWEEN ? AND ?
     ORDER BY dibuat_pada DESC"
);
$stmt->execute([$dari, $sampai]);
$pesananList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalOmzet = array_sum(array_column($pesananList, 'total_harga'));
$totalTransaksi = count($pesananList);

$stmtTerlaris = $koneksi->prepare(
    "SELECT dp.nama_produk, SUM(dp.jumlah) AS total_terjual
     FROM detail_pesanan dp
     JOIN pesanan p ON p.id_pesanan = dp.id_pesanan
     WHERE p.dibuat_pada::date BETWEEN ? AND ?
     GROUP BY dp.nama_produk
     ORDER BY total_terjual DESC
     LIMIT 5"
);
$stmtTerlaris->execute([$dari, $sampai]);
$produkTerlaris = $stmtTerlaris->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Penjualan — Batik Kirana Nusantara</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-shell">

<div class="admin-topbar">
  <div class="wrap">
    <span>Masuk sebagai <b><?= htmlspecialchars($_SESSION['admin_username']) ?></b></span>
    <div class="admin-topbar-actions">
      <a href="../index.php" target="_blank" rel="noopener">Lihat Beranda Toko ↗</a>
      <a href="logout.php">Keluar</a>
    </div>
  </div>
</div>

<div class="wrap admin-subnav">
  <a href="dashboard.php">Kelola Produk</a>
  <a href="kategori.php">Kelola Kategori</a>
  <a href="pesanan.php">Pesanan Masuk</a>
  <a href="transaksi_baru.php">Transaksi Manual</a>
  <a href="laporan.php" class="admin-subnav-aktif">Laporan Penjualan</a>
</div>

<div class="wrap" style="padding-top:24px; padding-bottom:60px;">
  <h2 class="section-title" style="margin-top:0;">Laporan Penjualan</h2>

  <form method="GET" action="laporan.php" class="laporan-filter">
    <div class="form-field">
      <label for="dari">Dari Tanggal</label>
      <input type="date" id="dari" name="dari" value="<?= htmlspecialchars($dari) ?>">
    </div>
    <div class="form-field">
      <label for="sampai">Sampai Tanggal</label>
      <input type="date" id="sampai" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
    </div>
    <button type="submit" class="btn btn-gold">Terapkan Filter</button>
    <a href="ekspor_laporan.php?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>" class="btn btn-outline">⬇ Ekspor ke Excel</a>
  </form>

  <div class="laporan-ringkasan">
    <div class="laporan-kartu">
      <span>Total Transaksi</span>
      <strong><?= $totalTransaksi ?></strong>
    </div>
    <div class="laporan-kartu">
      <span>Total Omzet</span>
      <strong>Rp <?= number_format($totalOmzet, 0, ',', '.') ?></strong>
    </div>
  </div>

  <h3>Produk Terlaris</h3>
  <table class="admin-table" style="margin-bottom:32px;">
    <thead><tr><th>Produk</th><th>Total Terjual</th></tr></thead>
    <tbody>
      <?php if (count($produkTerlaris) > 0): ?>
        <?php foreach ($produkTerlaris as $row): ?>
          <tr><td><?= htmlspecialchars($row['nama_produk']) ?></td><td><?= (int)$row['total_terjual'] ?> unit</td></tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="2">Belum ada transaksi pada rentang tanggal ini.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>Rincian Transaksi</h3>
  <table class="admin-table">
    <thead><tr><th>Kode</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>
    <tbody>
      <?php if (count($pesananList) > 0): ?>
        <?php foreach ($pesananList as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['kode_pesanan']) ?></td>
            <td><?= htmlspecialchars($p['nama_pelanggan']) ?></td>
            <td>Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></td>
            <td><?= htmlspecialchars($p['status']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($p['dibuat_pada'])) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5">Tidak ada transaksi pada rentang tanggal ini.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>