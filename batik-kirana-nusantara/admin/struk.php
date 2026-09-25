<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$baru = isset($_GET['baru']);

$stmt = $koneksi->prepare("SELECT * FROM pesanan WHERE id_pesanan = ?");
$stmt->execute([$id_pesanan]);
$pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pesanan) {
    header('Location: pesanan.php');
    exit;
}

$stmtDetail = $koneksi->prepare("SELECT * FROM detail_pesanan WHERE id_pesanan = ?");
$stmtDetail->execute([$id_pesanan]);
$item = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$adaPembayaran = $pesanan['uang_diterima'] !== null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk <?= htmlspecialchars($pesanan['kode_pesanan']) ?> — Batik Kirana Nusantara</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-shell">

<div class="admin-topbar no-print">
  <div class="wrap">
    <span>Masuk sebagai <b><?= htmlspecialchars($_SESSION['admin_username']) ?></b></span>
    <div class="admin-topbar-actions">
      <a href="../index.php" target="_blank" rel="noopener">Lihat Beranda Toko ↗</a>
      <a href="logout.php">Keluar</a>
    </div>
  </div>
</div>

<div class="wrap admin-subnav no-print">
  <a href="dashboard.php">Dashboard</a>
  <a href="kategori.php">Kelola Kategori</a>
  <a href="pesanan.php">Pesanan Masuk</a>
  <a href="transaksi_baru.php">Transaksi Manual</a>
  <a href="laporan.php">Laporan Penjualan</a>
</div>

<div class="wrap" style="padding-top:24px; padding-bottom:60px;">

  <?php if ($baru): ?>
    <p class="alert alert-success no-print">Transaksi <strong><?= htmlspecialchars($pesanan['kode_pesanan']) ?></strong> berhasil disimpan. Silakan cetak struk di bawah ini.</p>
  <?php endif; ?>

  <div class="struk-kertas">
    <div class="struk-kop">
      <p class="struk-toko-nama">Batik Kirana Nusantara</p>
      <p class="struk-toko-sub">Batik Tulis &amp; Batik Cap Karya Perajin Lokal</p>
    </div>

    <div class="struk-status-bar">
      <span class="struk-badge struk-badge-<?= strtolower(str_replace(' ', '-', $pesanan['status'])) ?>"><?= htmlspecialchars($pesanan['status']) ?></span>
    </div>

    <div class="struk-meta">
      <div><span>No. Transaksi</span><strong><?= htmlspecialchars($pesanan['kode_pesanan']) ?></strong></div>
      <div><span>Tanggal</span><strong><?= date('d/m/Y H:i', strtotime($pesanan['dibuat_pada'])) ?></strong></div>
      <div><span>Pelanggan</span><strong><?= htmlspecialchars($pesanan['nama_pelanggan']) ?></strong></div>
      <div><span>Metode Bayar</span><strong><?= htmlspecialchars($pesanan['metode_pembayaran']) ?></strong></div>
    </div>

    <table class="struk-item-table">
      <thead>
        <tr><th>Produk</th><th class="num">Jml</th><th class="num">Harga</th><th class="num">Subtotal</th></tr>
      </thead>
      <tbody>
        <?php foreach ($item as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['nama_produk']) ?></td>
            <td class="num"><?= (int)$d['jumlah'] ?></td>
            <td class="num"><?= number_format($d['harga_satuan'], 0, ',', '.') ?></td>
            <td class="num"><?= number_format($d['subtotal'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="struk-total-blok">
      <div class="struk-total-utama"><span>Total</span><span>Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span></div>
      <?php if ($adaPembayaran): ?>
        <div><span>Pembayaran</span><span>Rp <?= number_format($pesanan['uang_diterima'], 0, ',', '.') ?></span></div>
        <div><span>Kembalian</span><span>Rp <?= number_format($pesanan['kembalian'], 0, ',', '.') ?></span></div>
      <?php else: ?>
        <div class="struk-catatan">Pembayaran dilakukan lewat pesanan online, tidak dicatat manual.</div>
      <?php endif; ?>
    </div>

    <div class="struk-footer">
      <p class="struk-footer-utama">Terima kasih telah berbelanja di Batik Kirana Nusantara</p>
      <p class="struk-footer-sub">Struk ini adalah bukti transaksi yang sah</p>
    </div>
  </div>

  <div class="struk-aksi no-print">
    <button type="button" class="btn btn-gold" onclick="window.print()">Cetak Struk</button>
    <a href="pesanan.php" class="btn btn-outline">Kembali ke Pesanan</a>
  </div>

</div>

</body>
</html>
