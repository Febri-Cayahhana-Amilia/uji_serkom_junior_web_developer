<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$statusPilihan = ['Menunggu Pembayaran', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'];
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ubah_status') {
    $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
    $status_baru = $_POST['status'] ?? '';
    if (in_array($status_baru, $statusPilihan, true) && $id_pesanan > 0) {
        $stmt = $koneksi->prepare("UPDATE pesanan SET status = ? WHERE id_pesanan = ?");
        $stmt->execute([$status_baru, $id_pesanan]);
        $sukses = 'Status pesanan berhasil diperbarui.';
    }
}

$pesananList = $koneksi->query(
    "SELECT id_pesanan, kode_pesanan, nama_pelanggan, telepon, total_harga, status, dibuat_pada
     FROM pesanan ORDER BY dibuat_pada DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$idDetailLihat = isset($_GET['lihat']) ? (int)$_GET['lihat'] : 0;
$detailItems = [];
if ($idDetailLihat > 0) {
    $stmtDetail = $koneksi->prepare("SELECT * FROM detail_pesanan WHERE id_pesanan = ?");
    $stmtDetail->execute([$idDetailLihat]);
    $detailItems = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Masuk — Batik Kirana Nusantara</title>
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
  <a href="dashboard.php">Dashboard</a>
  <a href="kategori.php">Kelola Kategori</a>
  <a href="pesanan.php" class="admin-subnav-aktif">Pesanan Masuk</a>
  <a href="transaksi_baru.php">Transaksi Manual</a>
  <a href="laporan.php">Laporan Penjualan</a>
</div>

<div class="wrap" style="padding-top:24px; padding-bottom:60px;">
  <div class="admin-toolbar">
    <h2 class="section-title" style="margin:0;">Pesanan Masuk</h2>
    <a href="transaksi_baru.php" class="btn btn-gold">+ Buat Transaksi Manual</a>
  </div>

  <?php if ($sukses): ?><p class="alert alert-success"><?= htmlspecialchars($sukses) ?></p><?php endif; ?>
  <?php if (!empty($_GET['dibuat'])): ?>
    <p class="alert alert-success">Transaksi <strong><?= htmlspecialchars($_GET['dibuat']) ?></strong> berhasil dibuat dari halaman admin.</p>
  <?php endif; ?>

  <table class="admin-table">
    <thead>
      <tr>
        <th>Kode</th><th>Pelanggan</th><th>Telepon</th><th>Total</th><th>Status</th><th>Tanggal</th><th>Aksi</th><th>Struk</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($pesananList) > 0): ?>
        <?php foreach ($pesananList as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['kode_pesanan']) ?></td>
            <td><?= htmlspecialchars($p['nama_pelanggan']) ?></td>
            <td><?= htmlspecialchars($p['telepon']) ?></td>
            <td>Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></td>
            <td>
              <form method="POST" action="pesanan.php" class="keranjang-qty-form">
                <input type="hidden" name="aksi" value="ubah_status">
                <input type="hidden" name="id_pesanan" value="<?= (int)$p['id_pesanan'] ?>">
                <select name="status" onchange="this.form.submit()">
                  <?php foreach ($statusPilihan as $s): ?>
                    <option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td><?= date('d/m/Y H:i', strtotime($p['dibuat_pada'])) ?></td>
            <td><a href="pesanan.php?lihat=<?= (int)$p['id_pesanan'] ?>#detail" class="link-edit">Lihat Item</a></td>
            <td><a href="struk.php?id=<?= (int)$p['id_pesanan'] ?>" class="link-edit" target="_blank" rel="noopener">🖨 Cetak</a></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="8">Belum ada pesanan masuk.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if ($idDetailLihat > 0): ?>
    <div id="detail" style="margin-top:32px;">
      <h3>Detail Pesanan #<?= (int)$idDetailLihat ?></h3>
      <table class="admin-table">
        <thead><tr><th>Produk</th><th>Harga Satuan</th><th>Jumlah</th><th>Subtotal</th></tr></thead>
        <tbody>
          <?php foreach ($detailItems as $d): ?>
            <tr>
              <td><?= htmlspecialchars($d['nama_produk']) ?></td>
              <td>Rp <?= number_format($d['harga_satuan'], 0, ',', '.') ?></td>
              <td><?= (int)$d['jumlah'] ?></td>
              <td>Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

</body>
</html>