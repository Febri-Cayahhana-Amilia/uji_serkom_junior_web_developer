<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

// Validasi sederhana format tanggal supaya tidak dipakai untuk hal aneh-aneh
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) $dari = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date('Y-m-d');

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
     ORDER BY total_terjual DESC"
);
$stmtTerlaris->execute([$dari, $sampai]);
$produkTerlaris = $stmtTerlaris->fetchAll(PDO::FETCH_ASSOC);

// ---------- Siapkan file CSV (dibuka langsung oleh Excel) ----------
$nama_file = 'laporan-penjualan_' . $dari . '_sampai_' . $sampai . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nama_file . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// BOM UTF-8 supaya Excel menampilkan karakter "Rp", "×", dsb. dengan benar
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['Laporan Penjualan - Batik Kirana Nusantara']);
fputcsv($out, ['Periode', $dari . ' s/d ' . $sampai]);
fputcsv($out, []);
fputcsv($out, ['Total Transaksi', $totalTransaksi]);
fputcsv($out, ['Total Omzet', $totalOmzet]);
fputcsv($out, []);

fputcsv($out, ['Produk Terlaris']);
fputcsv($out, ['Nama Produk', 'Total Terjual']);
foreach ($produkTerlaris as $row) {
    fputcsv($out, [$row['nama_produk'], (int)$row['total_terjual']]);
}
fputcsv($out, []);

fputcsv($out, ['Rincian Transaksi']);
fputcsv($out, ['Kode Pesanan', 'Nama Pelanggan', 'Email', 'Telepon', 'Metode Pembayaran', 'Total', 'Status', 'Tanggal']);
foreach ($pesananList as $p) {
    fputcsv($out, [
        $p['kode_pesanan'],
        $p['nama_pelanggan'],
        $p['email'],
        $p['telepon'],
        $p['metode_pembayaran'],
        $p['total_harga'],
        $p['status'],
        date('d/m/Y H:i', strtotime($p['dibuat_pada'])),
    ]);
}

fclose($out);
exit;