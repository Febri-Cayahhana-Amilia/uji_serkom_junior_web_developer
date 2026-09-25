<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$kategoriList = $koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);

$periode = $_GET['periode'] ?? 'bulan_ini';
$idKategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

// ---------- Tentukan rentang tanggal dari preset periode (atau rentang kustom) ----------
$hariIni = date('Y-m-d');
switch ($periode) {
    case 'hari_ini':
        $dari = $hariIni;
        $sampai = $hariIni;
        break;
    case '7_hari':
        $dari = date('Y-m-d', strtotime('-6 days'));
        $sampai = $hariIni;
        break;
    case 'bulan_ini':
        $dari = date('Y-m-01');
        $sampai = $hariIni;
        break;
    case 'kustom':
    default:
        $periode = 'kustom';
        $dari = $_GET['dari'] ?? date('Y-m-01');
        $sampai = $_GET['sampai'] ?? $hariIni;
        break;
}

// ---------- Validasi format & urutan tanggal ----------
$errorTanggal = '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) $dari = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = $hariIni;
if ($dari > $sampai) {
    $errorTanggal = 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir. Menampilkan rentang tanggal yang sudah ditukar otomatis.';
    [$dari, $sampai] = [$sampai, $dari];
}

// ---------- Query utama: rincian transaksi (per pesanan), dengan filter kategori opsional ----------
$paramsUtama = [$dari, $sampai];
$whereKategori = '';
if ($idKategori > 0) {
    $whereKategori = ' AND pr.id_kategori = ?';
    $paramsUtama[] = $idKategori;
}

$sqlUtama = "
    SELECT
        p.id_pesanan, p.kode_pesanan, p.nama_pelanggan, p.status, p.dibuat_pada,
        SUM(dp.jumlah)   AS jumlah_terkait,
        SUM(dp.subtotal) AS total_terkait,
        STRING_AGG(DISTINCT dp.nama_produk, ', ') AS daftar_produk
    FROM pesanan p
    JOIN detail_pesanan dp ON dp.id_pesanan = p.id_pesanan
    LEFT JOIN produk pr ON pr.id_produk = dp.id_produk
    WHERE p.dibuat_pada::date BETWEEN ? AND ?" . $whereKategori . "
    GROUP BY p.id_pesanan, p.kode_pesanan, p.nama_pelanggan, p.status, p.dibuat_pada
    ORDER BY p.dibuat_pada DESC
";
$stmt = $koneksi->prepare($sqlUtama);
$stmt->execute($paramsUtama);
$pesananList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalTransaksi = count($pesananList);
$totalBarang = (int) array_sum(array_column($pesananList, 'jumlah_terkait'));
$totalOmzet = array_sum(array_column($pesananList, 'total_terkait'));

// ---------- Produk terlaris (rentang & kategori yang sama) ----------
$paramsTerlaris = [$dari, $sampai];
$whereKategoriTerlaris = '';
if ($idKategori > 0) {
    $whereKategoriTerlaris = ' AND pr.id_kategori = ?';
    $paramsTerlaris[] = $idKategori;
}
$stmtTerlaris = $koneksi->prepare(
    "SELECT dp.nama_produk, SUM(dp.jumlah) AS total_terjual
     FROM detail_pesanan dp
     JOIN pesanan p ON p.id_pesanan = dp.id_pesanan
     LEFT JOIN produk pr ON pr.id_produk = dp.id_produk
     WHERE p.dibuat_pada::date BETWEEN ? AND ?" . $whereKategoriTerlaris . "
     GROUP BY dp.nama_produk
     ORDER BY total_terjual DESC
     LIMIT 5"
);
$stmtTerlaris->execute($paramsTerlaris);
$produkTerlaris = $stmtTerlaris->fetchAll(PDO::FETCH_ASSOC);

$namaKategoriAktif = '';
if ($idKategori > 0) {
    foreach ($kategoriList as $k) {
        if ((int)$k['id_kategori'] === $idKategori) { $namaKategoriAktif = $k['nama_kategori']; break; }
    }
}

$labelPeriode = [
    'hari_ini' => 'Hari Ini',
    '7_hari'   => '7 Hari Terakhir',
    'bulan_ini' => 'Bulan Ini',
    'kustom'   => 'Rentang Kustom',
][$periode] ?? 'Rentang Kustom';
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
  <a href="dashboard.php">Dashboard</a>
  <a href="kategori.php">Kelola Kategori</a>
  <a href="pesanan.php">Pesanan Masuk</a>
  <a href="transaksi_baru.php">Transaksi Manual</a>
  <a href="laporan.php" class="admin-subnav-aktif">Laporan Penjualan</a>
</div>

<div class="wrap" style="padding-top:24px; padding-bottom:60px;">
  <h2 class="section-title" style="margin-top:0;">Laporan Penjualan</h2>

  <!-- ---------- Kop laporan: hanya tampil saat dicetak ---------- -->
  <div class="cetak-laporan-kop">
    <h2>Batik Kirana Nusantara</h2>
    <p>Laporan Penjualan — Periode: <?= htmlspecialchars($labelPeriode) ?> (<?= htmlspecialchars($dari) ?> s/d <?= htmlspecialchars($sampai) ?>)</p>
    <?php if ($namaKategoriAktif): ?><p>Kategori: <?= htmlspecialchars($namaKategoriAktif) ?></p><?php endif; ?>
  </div>

  <?php if ($errorTanggal): ?><p class="alert alert-error no-print"><?= htmlspecialchars($errorTanggal) ?></p><?php endif; ?>

  <form method="GET" action="laporan.php" class="no-print">
    <div class="laporan-periode-cepat">
      <?php
        $presets = ['hari_ini' => 'Hari Ini', '7_hari' => '7 Hari Terakhir', 'bulan_ini' => 'Bulan Ini', 'kustom' => 'Rentang Kustom'];
        foreach ($presets as $key => $label):
      ?>
        <button type="submit" name="periode" value="<?= $key ?>"
                class="laporan-periode-btn <?= $periode === $key ? 'aktif' : '' ?>"><?= $label ?></button>
      <?php endforeach; ?>
    </div>

    <div class="laporan-filter" style="margin-top:14px;">
      <div class="form-field">
        <label for="dari">Dari Tanggal</label>
        <input type="date" id="dari" name="dari" value="<?= htmlspecialchars($dari) ?>">
      </div>
      <div class="form-field">
        <label for="sampai">Sampai Tanggal</label>
        <input type="date" id="sampai" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
      </div>
      <div class="form-field">
        <label for="kategori">Kategori Produk</label>
        <select id="kategori" name="kategori">
          <option value="0">Semua Kategori</option>
          <?php foreach ($kategoriList as $k): ?>
            <option value="<?= (int)$k['id_kategori'] ?>" <?= $idKategori === (int)$k['id_kategori'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama_kategori']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" name="periode" value="kustom" class="btn btn-gold">Terapkan Filter</button>
      <a href="laporan.php" class="btn btn-outline">Reset Filter</a>
      <button type="button" class="btn btn-outline" onclick="window.print()">🖨 Cetak Laporan</button>
      <a href="ekspor_laporan.php?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&kategori=<?= (int)$idKategori ?>&periode=<?= urlencode($periode) ?>" class="btn btn-outline">⬇ Ekspor ke Excel</a>
    </div>
  </form>

  <div class="laporan-ringkasan">
    <div class="laporan-kartu">
      <span>Total Transaksi</span>
      <strong><?= $totalTransaksi ?></strong>
    </div>
    <div class="laporan-kartu">
      <span>Total Barang Terjual</span>
      <strong><?= $totalBarang ?> unit</strong>
    </div>
    <div class="laporan-kartu">
      <span>Total Penjualan</span>
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
        <tr><td colspan="2">Belum ada transaksi pada rentang/kategori ini.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>Rincian Transaksi</h3>
  <table class="admin-table">
    <thead><tr><th>No</th><th>Nomor Transaksi</th><th>Tanggal</th><th>Produk</th><th>Jumlah</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
      <?php if (count($pesananList) > 0): ?>
        <?php foreach ($pesananList as $i => $p): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($p['kode_pesanan']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($p['dibuat_pada'])) ?></td>
            <td><?= htmlspecialchars($p['daftar_produk']) ?></td>
            <td><?= (int)$p['jumlah_terkait'] ?></td>
            <td>Rp <?= number_format($p['total_terkait'], 0, ',', '.') ?></td>
            <td><?= htmlspecialchars($p['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7">Tidak ada transaksi pada rentang/kategori ini.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
