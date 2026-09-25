<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$produkList = $koneksi->query(
    "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar, p.ukuran_tersedia, k.nama_kategori
     FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori
     ORDER BY p.id_produk DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// ---------- Statistik ringkas dashboard (semua dihitung langsung dari database) ----------
$totalProduk = (int) $koneksi->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$totalKategoriDb = (int) $koneksi->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
$totalTransaksiDb = (int) $koneksi->query("SELECT COUNT(*) FROM pesanan")->fetchColumn();
$totalPenjualanDb = (float) $koneksi->query("SELECT COALESCE(SUM(total_harga),0) FROM pesanan")->fetchColumn();
$totalBarangTerjualDb = (int) $koneksi->query("SELECT COALESCE(SUM(jumlah),0) FROM detail_pesanan")->fetchColumn();

// ---------- Tren 7 hari terakhir vs 7 hari sebelumnya ----------
$stmtTren = $koneksi->prepare(
    "SELECT
        COALESCE(SUM(total_harga) FILTER (WHERE dibuat_pada::date >= ?), 0) AS periode_ini,
        COALESCE(SUM(total_harga) FILTER (WHERE dibuat_pada::date >= ? AND dibuat_pada::date < ?), 0) AS periode_lalu
     FROM pesanan
     WHERE dibuat_pada::date >= ?"
);
$mulai7 = date('Y-m-d', strtotime('-6 days'));
$mulai14 = date('Y-m-d', strtotime('-13 days'));
$stmtTren->execute([$mulai7, $mulai14, $mulai7, $mulai14]);
$tren = $stmtTren->fetch(PDO::FETCH_ASSOC) ?: ['periode_ini' => 0, 'periode_lalu' => 0];
$omzet7Ini = (float) $tren['periode_ini'];
$omzet7Lalu = (float) $tren['periode_lalu'];
if ($omzet7Lalu > 0) {
    $persenTren = round((($omzet7Ini - $omzet7Lalu) / $omzet7Lalu) * 100);
} else {
    $persenTren = $omzet7Ini > 0 ? 100 : 0;
}
$trenNaik = $persenTren >= 0;

// ---------- Grafik 1: pendapatan 14 hari terakhir ----------
$stmtGrafikTgl = $koneksi->prepare(
    "SELECT dibuat_pada::date AS tgl, SUM(total_harga) AS omzet
     FROM pesanan
     WHERE dibuat_pada::date >= ?
     GROUP BY tgl ORDER BY tgl"
);
$stmtGrafikTgl->execute([$mulai14]);
$omzetPerTanggal = [];
foreach ($stmtGrafikTgl->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $omzetPerTanggal[$row['tgl']] = (float) $row['omzet'];
}
$labelGrafikTanggal = [];
$dataGrafikTanggal = [];
for ($i = 13; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $labelGrafikTanggal[] = date('d/m', strtotime($tgl));
    $dataGrafikTanggal[] = $omzetPerTanggal[$tgl] ?? 0;
}
$omzet14Hari = array_sum($dataGrafikTanggal);

// ---------- Grafik 2: 5 produk paling banyak terjual ----------
$stmtGrafikProduk = $koneksi->query(
    "SELECT nama_produk, SUM(jumlah) AS total_terjual
     FROM detail_pesanan
     GROUP BY nama_produk
     ORDER BY total_terjual DESC
     LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// ---------- Grafik 3: komposisi pendapatan per metode pembayaran ----------
$stmtGrafikMetode = $koneksi->query(
    "SELECT split_part(metode_pembayaran, ' - ', 1) AS metode, SUM(total_harga) AS omzet
     FROM pesanan
     GROUP BY metode
     ORDER BY omzet DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// ==================== Helper murni PHP untuk gambar grafik (SVG/CSS, tanpa library luar) ====================

function fmtSingkat($n): string
{
    $n = (float) $n;
    if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . 'jt';
    if ($n >= 1000) return round($n / 1000) . 'rb';
    return (string) round($n);
}

/** Grafik batang pendapatan (SVG), tanpa dependensi apa pun. */
function renderGrafikArea(array $label, array $data): void
{
    $n = count($data);
    if ($n < 2) { echo '<div class="dash-grafik-kosong">Data belum cukup untuk ditampilkan.</div>'; return; }

    $lebar = 700; $tinggi = 230;
    $padKiri = 44; $padKanan = 16; $padAtas = 18; $padBawah = 34;
    $areaW = $lebar - $padKiri - $padKanan;
    $areaH = $tinggi - $padAtas - $padBawah;
    $maks = max($data); if ($maks <= 0) $maks = 1;
    $baseY = $padAtas + $areaH;

    // lebar tiap slot & lebar batang (dengan jarak antar batang)
    $slotW = $areaW / $n;
    $barW = max(6, $slotW * 0.55);
    ?>
    <svg viewBox="0 0 <?= $lebar ?> <?= $tinggi ?>" class="dash-svg-chart" preserveAspectRatio="none">
      <defs>
        <linearGradient id="gradPendapatan" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#B9862F"/>
          <stop offset="100%" stop-color="#D9A94F"/>
        </linearGradient>
      </defs>
      <?php for ($g = 0; $g <= 2; $g++):
        $gy = round($padAtas + $areaH * $g / 2, 1);
        $gv = fmtSingkat($maks - ($maks * $g / 2)); ?>
        <line x1="<?= $padKiri ?>" y1="<?= $gy ?>" x2="<?= $lebar - $padKanan ?>" y2="<?= $gy ?>" class="dash-svg-grid"/>
        <text x="2" y="<?= $gy + 4 ?>" class="dash-svg-label-y">Rp<?= $gv ?></text>
      <?php endfor; ?>
      <?php for ($i = 0; $i < $n; $i++):
        $terakhir = $i === $n - 1;
        $tinggiBar = $data[$i] / $maks * $areaH;
        $x = $padKiri + ($slotW * $i) + (($slotW - $barW) / 2);
        $y = $baseY - $tinggiBar;
        $xTengah = $x + ($barW / 2);
        ?>
        <rect x="<?= round($x, 1) ?>" y="<?= round($y, 1) ?>" width="<?= round($barW, 1) ?>" height="<?= round($tinggiBar, 1) ?>"
              rx="3" fill="url(#gradPendapatan)" class="dash-svg-bar<?= $terakhir ? ' dash-svg-bar-aktif' : '' ?>">
          <title><?= $label[$i] ?>: Rp <?= number_format($data[$i], 0, ',', '.') ?></title>
        </rect>
        <?php if ($i % 2 === 0 || $terakhir): ?>
          <text x="<?= round($xTengah, 1) ?>" y="<?= $baseY + 20 ?>" class="dash-svg-label-x"><?= $label[$i] ?></text>
        <?php endif; ?>
      <?php endfor; ?>
    </svg>
    <?php
}

/** Bar chart horizontal murni CSS/HTML untuk produk terlaris. */
function renderGrafikBar(array $rows): void
{
    if (empty($rows)) { echo '<div class="dash-grafik-kosong">Belum ada produk terjual untuk ditampilkan.</div>'; return; }
    $maks = max(array_column($rows, 'total_terjual')) ?: 1;
    foreach ($rows as $row):
        $persen = max(6, round($row['total_terjual'] / $maks * 100));
        ?>
        <div class="dash-bar-row">
          <span class="dash-bar-label"><?= htmlspecialchars($row['nama_produk']) ?></span>
          <div class="dash-bar-track">
            <div class="dash-bar-fill" style="width:<?= $persen ?>%;"></div>
          </div>
          <span class="dash-bar-nilai"><?= (int) $row['total_terjual'] ?></span>
        </div>
    <?php endforeach;
}

/** Donat komposisi metode pembayaran, dibuat dengan conic-gradient CSS murni + legenda. */
function renderGrafikDonat(array $rows): void
{
    if (empty($rows)) { echo '<div class="dash-grafik-kosong">Belum ada transaksi untuk ditampilkan.</div>'; return; }
    $warna = ['#1F3A5A', '#B9862F', '#7A4B2A', '#5C8AA6', '#C97B4A', '#6B9E6F'];
    $total = array_sum(array_column($rows, 'omzet')) ?: 1;

    $stops = []; $kumulatif = 0;
    foreach ($rows as $i => $row) {
        $mulai = round($kumulatif / $total * 360, 2);
        $kumulatif += $row['omzet'];
        $selesai = round($kumulatif / $total * 360, 2);
        $stops[] = ($warna[$i % count($warna)]) . ' ' . $mulai . 'deg ' . $selesai . 'deg';
    }
    $gradientCss = implode(', ', $stops);
    ?>
    <div class="dash-donat-wrap">
      <div class="dash-donat" style="background: conic-gradient(<?= $gradientCss ?>);">
        <div class="dash-donat-hole">
          <span>Total</span>
          <strong>Rp <?= fmtSingkat($total) ?></strong>
        </div>
      </div>
      <ul class="dash-donat-legenda">
        <?php foreach ($rows as $i => $row):
          $persen = round($row['omzet'] / $total * 100); ?>
          <li>
            <span class="dash-donat-dot" style="background:<?= $warna[$i % count($warna)] ?>;"></span>
            <span class="dash-donat-nama"><?= htmlspecialchars($row['metode']) ?></span>
            <span class="dash-donat-persen"><?= $persen ?>%</span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — Batik Kirana Nusantara</title>
<link rel="stylesheet" href="../css/style.css">
<style>
/* ==================== Modal Konfirmasi Custom (pengganti confirm() bawaan browser) ==================== */
.modal-konfirmasi-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(20, 20, 20, 0.55);
  align-items: center;
  justify-content: center;
  z-index: 999;
  padding: 16px;
}
.modal-konfirmasi-overlay.aktif {
  display: flex;
}
.modal-konfirmasi-box {
  background: #fff;
  border-radius: 12px;
  padding: 26px 28px;
  width: 100%;
  max-width: 380px;
  box-shadow: 0 16px 40px rgba(0,0,0,0.28);
  animation: modalKonfirmasiMuncul 0.16s ease-out;
  text-align: left;
}
@keyframes modalKonfirmasiMuncul {
  from { opacity: 0; transform: translateY(-10px) scale(0.98); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
.modal-konfirmasi-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #FBEAE6;
  color: #A3402C;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
}
.modal-konfirmasi-icon svg {
  width: 22px;
  height: 22px;
}
.modal-konfirmasi-box h4 {
  margin: 0 0 8px;
  color: var(--indigo, #1F3A5A);
  font-size: 17px;
}
.modal-konfirmasi-box p {
  margin: 0 0 22px;
  color: #555;
  font-size: 14px;
  line-height: 1.5;
}
.modal-konfirmasi-aksi {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
.modal-konfirmasi-aksi .btn {
  cursor: pointer;
  border: none;
  font-size: 14px;
  padding: 9px 18px;
  border-radius: 7px;
}
.btn-batal-konfirmasi {
  background: #eee;
  color: #333;
}
.btn-batal-konfirmasi:hover {
  background: #e0e0e0;
}
.btn-hapus-konfirmasi {
  background: #A3402C;
  color: #fff;
}
.btn-hapus-konfirmasi:hover {
  background: #8c3524;
}

/* ==================== Notifikasi Toast (pengganti alert bawaan browser, opsional) ==================== */
.toast-notif-wrap {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.toast-notif {
  background: #1F3A5A;
  color: #fff;
  padding: 12px 18px;
  border-radius: 8px;
  font-size: 14px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
  min-width: 240px;
  animation: toastMasuk 0.2s ease-out;
}
.toast-notif.sukses { background: #2E7D4F; }
.toast-notif.gagal  { background: #A3402C; }
@keyframes toastMasuk {
  from { opacity: 0; transform: translateX(20px); }
  to   { opacity: 1; transform: translateX(0); }
}

/* ==================== Diagram Batang Pendapatan ==================== */
.dash-svg-bar {
  transition: opacity 0.15s ease;
}
.dash-svg-bar:hover {
  opacity: 0.8;
}
.dash-svg-bar-aktif {
  filter: drop-shadow(0 0 2px rgba(185, 134, 47, 0.5));
}
</style>
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
  <a href="dashboard.php" class="admin-subnav-aktif">Dashboard</a>
  <a href="kategori.php">Kelola Kategori</a>
  <a href="pesanan.php">Pesanan Masuk</a>
  <a href="transaksi_baru.php">Transaksi Manual</a>
  <a href="laporan.php">Laporan Penjualan</a>
</div>

<div class="wrap" style="padding-top:24px; padding-bottom:60px;">
  <div class="dash-header">
    <div>
      <h2 class="section-title" style="margin:0;">Dashboard</h2>
      <p class="dash-header-sub">Ringkasan performa toko Batik Kirana Nusantara hari ini, <?= date('d F Y') ?>.</p>
    </div>
  </div>

  <div class="dash-kartu-grid">
    <div class="dash-kartu dash-kartu-indigo">
      <div class="dash-kartu-icon">
        <svg viewBox="0 0 24 24" fill="none"><path d="M4 16l4-5 3 3 5-7 4 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="dash-kartu-body">
        <span>Total Produk</span>
        <strong><?= $totalProduk ?></strong>
      </div>
    </div>

    <div class="dash-kartu dash-kartu-soga">
      <div class="dash-kartu-icon">
        <svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="13" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="4" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="13" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/></svg>
      </div>
      <div class="dash-kartu-body">
        <span>Total Kategori</span>
        <strong><?= $totalKategoriDb ?></strong>
      </div>
    </div>

    <div class="dash-kartu dash-kartu-gold">
      <div class="dash-kartu-icon">
        <svg viewBox="0 0 24 24" fill="none"><path d="M3 7h18M3 7v11a1 1 0 001 1h16a1 1 0 001-1V7M3 7l2-4h14l2 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="dash-kartu-body">
        <span>Total Transaksi</span>
        <strong><?= $totalTransaksiDb ?></strong>
      </div>
    </div>

    <div class="dash-kartu dash-kartu-indigo dash-kartu-besar">
      <div class="dash-kartu-icon">
        <svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20M17 6.5c0-2-2.2-3.5-5-3.5s-5 1.5-5 3.5 2.2 3.2 5 3.5c2.8.3 5 1.5 5 3.5s-2.2 3.5-5 3.5-5-1.5-5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <div class="dash-kartu-body">
        <span>Total Penjualan</span>
        <strong>Rp <?= number_format($totalPenjualanDb, 0, ',', '.') ?></strong>
        <div class="dash-kartu-tren <?= $trenNaik ? 'naik' : 'turun' ?>">
          <svg viewBox="0 0 24 24" fill="none" width="13" height="13">
            <?php if ($trenNaik): ?>
              <path d="M5 15l7-7 7 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
            <?php else: ?>
              <path d="M5 9l7 7 7-7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
            <?php endif; ?>
          </svg>
          <span><?= abs($persenTren) ?>% vs 7 hari sebelumnya</span>
        </div>
      </div>
    </div>

    <div class="dash-kartu dash-kartu-soga">
      <div class="dash-kartu-icon">
        <svg viewBox="0 0 24 24" fill="none"><path d="M20 7H4a1 1 0 00-1 1v3a2 2 0 000 4v3a1 1 0 001 1h16a1 1 0 001-1v-3a2 2 0 000-4V8a1 1 0 00-1-1z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
      </div>
      <div class="dash-kartu-body">
        <span>Total Barang Terjual</span>
        <strong><?= $totalBarangTerjualDb ?> unit</strong>
      </div>
    </div>
  </div>

  <div class="dash-grafik-utama">
    <div class="dash-grafik-utama-head">
      <div>
        <h4>Pendapatan 14 Hari Terakhir</h4>
        <p>Total periode ini <strong>Rp <?= number_format($omzet14Hari, 0, ',', '.') ?></strong></p>
      </div>
      <div class="dash-kartu-tren <?= $trenNaik ? 'naik' : 'turun' ?> dash-tren-badge">
        <svg viewBox="0 0 24 24" fill="none" width="13" height="13">
          <?php if ($trenNaik): ?>
            <path d="M5 15l7-7 7 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
          <?php else: ?>
            <path d="M5 9l7 7 7-7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
          <?php endif; ?>
        </svg>
        <span><?= abs($persenTren) ?>%</span>
      </div>
    </div>
    <?php if ($totalTransaksiDb > 0): ?>
      <?php renderGrafikArea($labelGrafikTanggal, $dataGrafikTanggal); ?>
    <?php else: ?>
      <div class="dash-grafik-kosong">Belum ada transaksi untuk ditampilkan.</div>
    <?php endif; ?>
  </div>

  <div class="dash-grafik-grid">
    <div class="dash-grafik-card">
      <h4>Kategori Paling Banyak Terjual</h4>
      <div class="dash-bar-list">
        <?php renderGrafikBar($stmtGrafikProduk); ?>
      </div>
    </div>
    <div class="dash-grafik-card">
      <h4>Komposisi Metode Pembayaran</h4>
      <?php renderGrafikDonat($stmtGrafikMetode); ?>
    </div>
  </div>

  <div class="admin-toolbar" style="margin-top:8px;">
    <h3 class="dash-section-title" style="margin:0;">Kelola Produk</h3>
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
        <th>Ukuran</th>
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
            <td>
              <?php if (!empty($row['ukuran_tersedia'])): ?>
                <span style="font-size:12.5px; color:var(--indigo);"><?= htmlspecialchars($row['ukuran_tersedia']) ?></span>
              <?php else: ?>
                <span style="font-size:12.5px; color:var(--soga);">—</span>
              <?php endif; ?>
            </td>
            <td class="admin-actions">
              <a href="edit.php?id=<?= (int)$row['id_produk'] ?>" class="link-edit">Edit</a>
              <form method="POST" action="hapus.php" id="form-hapus-<?= (int)$row['id_produk'] ?>" style="display:inline;">
                <input type="hidden" name="id" value="<?= (int)$row['id_produk'] ?>">
                <button type="button" class="link-hapus keranjang-hapus-btn"
                        onclick="konfirmasiHapus('form-hapus-<?= (int)$row['id_produk'] ?>', '<?= htmlspecialchars(addslashes($row['nama_produk'])) ?>')">
                  Hapus
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7">Belum ada produk.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- ==================== Modal Konfirmasi Hapus (pengganti confirm() bawaan browser) ==================== -->
<div id="modal-konfirmasi" class="modal-konfirmasi-overlay">
  <div class="modal-konfirmasi-box">
    <div class="modal-konfirmasi-icon">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14.18A1.5 1.5 0 003.5 20.5h17a1.5 1.5 0 001.39-2.46L13.71 3.86a1.5 1.5 0 00-2.42 0z"
              stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <h4>Konfirmasi Hapus Produk</h4>
    <p id="modal-konfirmasi-pesan">Yakin ingin menghapus produk ini?</p>
    <div class="modal-konfirmasi-aksi">
      <button type="button" class="btn btn-batal-konfirmasi" onclick="tutupModalKonfirmasi()">Batal</button>
      <button type="button" class="btn btn-hapus-konfirmasi" onclick="submitModalKonfirmasi()">Ya, Hapus</button>
    </div>
  </div>
</div>

<!-- Tempat munculnya notifikasi toast, jika suatu saat dibutuhkan -->
<div class="toast-notif-wrap" id="toast-notif-wrap"></div>

<script>
let formTargetHapus = null;

function konfirmasiHapus(formId, namaProduk) {
  formTargetHapus = document.getElementById(formId);
  document.getElementById('modal-konfirmasi-pesan').textContent =
    'Yakin ingin menghapus produk "' + namaProduk + '"? Tindakan ini tidak dapat dibatalkan.';
  document.getElementById('modal-konfirmasi').classList.add('aktif');
}

function tutupModalKonfirmasi() {
  formTargetHapus = null;
  document.getElementById('modal-konfirmasi').classList.remove('aktif');
}

function submitModalKonfirmasi() {
  if (formTargetHapus) {
    formTargetHapus.submit();
  }
}

// Tutup modal saat klik di luar kotak dialog
document.getElementById('modal-konfirmasi').addEventListener('click', function (e) {
  if (e.target === this) tutupModalKonfirmasi();
});

// Tutup modal dengan tombol Escape
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') tutupModalKonfirmasi();
});

// Fungsi bantu opsional: tampilkan notifikasi toast di dalam halaman
function tampilkanToast(pesan, jenis) {
  const wrap = document.getElementById('toast-notif-wrap');
  const el = document.createElement('div');
  el.className = 'toast-notif ' + (jenis || '');
  el.textContent = pesan;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}
</script>

</body>
</html>