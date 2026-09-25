<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$periode = $_GET['periode'] ?? 'kustom';
$idKategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

$hariIni = date('Y-m-d');
switch ($periode) {
    case 'hari_ini':
        $dari = $hariIni; $sampai = $hariIni; break;
    case '7_hari':
        $dari = date('Y-m-d', strtotime('-6 days')); $sampai = $hariIni; break;
    case 'bulan_ini':
        $dari = date('Y-m-01'); $sampai = $hariIni; break;
    default:
        $dari = $_GET['dari'] ?? date('Y-m-01');
        $sampai = $_GET['sampai'] ?? $hariIni;
        break;
}

// Validasi sederhana format & urutan tanggal supaya tidak dipakai untuk hal aneh-aneh
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) $dari = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = $hariIni;
if ($dari > $sampai) [$dari, $sampai] = [$sampai, $dari];

$paramsUtama = [$dari, $sampai];
$whereKategori = '';
if ($idKategori > 0) {
    $whereKategori = ' AND pr.id_kategori = ?';
    $paramsUtama[] = $idKategori;
}

$sqlUtama = "
    SELECT
        p.kode_pesanan, p.nama_pelanggan, p.email, p.telepon, p.metode_pembayaran, p.status, p.dibuat_pada,
        SUM(dp.jumlah)   AS jumlah_terkait,
        SUM(dp.subtotal) AS total_terkait,
        STRING_AGG(DISTINCT dp.nama_produk, ', ') AS daftar_produk
    FROM pesanan p
    JOIN detail_pesanan dp ON dp.id_pesanan = p.id_pesanan
    LEFT JOIN produk pr ON pr.id_produk = dp.id_produk
    WHERE p.dibuat_pada::date BETWEEN ? AND ?" . $whereKategori . "
    GROUP BY p.id_pesanan, p.kode_pesanan, p.nama_pelanggan, p.email, p.telepon, p.metode_pembayaran, p.status, p.dibuat_pada
    ORDER BY p.dibuat_pada DESC
";
$stmt = $koneksi->prepare($sqlUtama);
$stmt->execute($paramsUtama);
$pesananList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalTransaksi = count($pesananList);
$totalBarang = (int) array_sum(array_column($pesananList, 'jumlah_terkait'));
$totalOmzet = array_sum(array_column($pesananList, 'total_terkait'));

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
     ORDER BY total_terjual DESC"
);
$stmtTerlaris->execute($paramsTerlaris);
$produkTerlaris = $stmtTerlaris->fetchAll(PDO::FETCH_ASSOC);

$namaKategori = null;
if ($idKategori > 0) {
    $stmtNamaKat = $koneksi->prepare("SELECT nama_kategori FROM kategori WHERE id_kategori = ?");
    $stmtNamaKat->execute([$idKategori]);
    $namaKategori = $stmtNamaKat->fetchColumn() ?: null;
}

// ---------- Siapkan file Excel (.xls berbasis tabel HTML, dibaca native oleh Excel) ----------
// Tampilan sengaja polos seperti rekap biasa: satu baris judul kolom, lalu data.
$nama_file = 'rekap-penjualan_' . $dari . '_sampai_' . $sampai . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nama_file . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Teks (kode, telepon, dll) dipaksa sebagai teks supaya angka 0 di depan tidak hilang
function selTeks($isi): string
{
    return '<td style="mso-number-format:\'@\';">' . htmlspecialchars((string)$isi) . '</td>';
}

function selAngka($angka, string $format = '#,##0'): string
{
    return '<td style="mso-number-format:\'' . $format . '\';">' . htmlspecialchars((string)$angka) . '</td>';
}
?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
<meta charset="UTF-8">
<!--[if gte mso 9]>
<xml>
  <x:ExcelWorkbook>
    <x:ExcelWorksheets>
      <x:ExcelWorksheet>
        <x:Name>Rekap Penjualan</x:Name>
        <x:WorksheetOptions>
          <x:DisplayGridlines/>
          <x:FreezePanes/>
          <x:FrozenNoSplit/>
          <x:SplitHorizontal>1</x:SplitHorizontal>
          <x:TopRowBottomPane>1</x:TopRowBottomPane>
          <x:ActivePane>2</x:ActivePane>
        </x:WorksheetOptions>
      </x:ExcelWorksheet>
    </x:ExcelWorksheets>
  </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
  table { border-collapse: collapse; font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
  th { font-weight: bold; text-align: left; background: #D9D9D9; border: 0.5pt solid #A6A6A6; padding: 2px 6px; }
  td { border: 0.5pt solid #D9D9D9; padding: 2px 6px; vertical-align: middle; }
  tr.total td { font-weight: bold; border-top: 1pt solid #000000; }
</style>
</head>
<body>
<table>
  <col width="170"><col width="120"><col width="200"><col width="200"><col width="130">
  <col width="220"><col width="150"><col width="380"><col width="70"><col width="120">
  <tr>
    <th>Kode Pesanan</th>
    <th>Tanggal</th>
    <th>Nama Pelanggan</th>
    <th>Email</th>
    <th>Telepon</th>
    <th>Metode Pembayaran</th>
    <th>Status</th>
    <th>Produk</th>
    <th>Jumlah</th>
    <th>Total</th>
  </tr>
  <?php foreach ($pesananList as $p): ?>
  <tr>
    <?= selTeks($p['kode_pesanan']) ?>
    <?= selTeks(date('d/m/Y', strtotime($p['dibuat_pada']))) ?>
    <?= selTeks($p['nama_pelanggan']) ?>
    <?= selTeks($p['email']) ?>
    <?= selTeks($p['telepon']) ?>
    <?= selTeks($p['metode_pembayaran']) ?>
    <?= selTeks($p['status']) ?>
    <?= selTeks($p['daftar_produk']) ?>
    <?= selAngka((int)$p['jumlah_terkait']) ?>
    <?= selAngka((float)$p['total_terkait'], '"Rp"\ #,##0') ?>
  </tr>
  <?php endforeach; ?>
  <tr class="total">
    <td colspan="8">Total (<?= (int)$totalTransaksi ?> transaksi)</td>
    <?= selAngka($totalBarang) ?>
    <?= selAngka($totalOmzet, '"Rp"\ #,##0') ?>
  </tr>
</table>
</body>
</html>
<?php
exit;
