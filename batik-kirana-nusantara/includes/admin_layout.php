<?php
/**
 * Layout & helper bersama untuk semua halaman admin:
 * kepala halaman (topbar + navigasi), format rupiah, badge status.
 */

if (!function_exists('h')) {
    function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

function fmt_rupiah($n): string
{
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function badge_status(string $status): string
{
    $kelas = [
        'Menunggu Pembayaran' => 'badge-menunggu',
        'Diproses'            => 'badge-diproses',
        'Dikirim'             => 'badge-dikirim',
        'Selesai'             => 'badge-selesai',
        'Dibatalkan'          => 'badge-batal',
    ][$status] ?? 'badge-menunggu';
    return '<span class="badge ' . $kelas . '">' . h($status) . '</span>';
}

/** Topbar + navigasi admin. $aktif: dashboard|produk|kategori|pesanan|transaksi|laporan */
function admin_subnav(string $aktif): void
{
    $menu = [
        'dashboard' => ['dashboard.php', 'Dashboard'],
        'produk'    => ['produk.php', 'Kelola Produk'],
        'kategori'  => ['kategori.php', 'Kelola Kategori'],
        'pesanan'   => ['pesanan.php', 'Pesanan Masuk'],
        'transaksi' => ['transaksi_baru.php', 'Transaksi Manual'],
        'laporan'   => ['laporan.php', 'Laporan Penjualan'],
    ];
    echo '<div class="wrap admin-subnav">';
    foreach ($menu as $kunci => [$url, $label]) {
        $kelas = $kunci === $aktif ? ' class="admin-subnav-aktif"' : '';
        echo '<a href="' . $url . '"' . $kelas . '>' . $label . '</a>';
    }
    echo '</div>';
}

function admin_kepala(string $judul, string $aktif): void
{
    ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($judul) ?> — Batik Kirana Nusantara</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-shell">

<div class="admin-topbar">
  <div class="wrap">
    <span>Masuk sebagai <b><?= h($_SESSION['admin_username'] ?? '') ?></b></span>
    <div class="admin-topbar-actions">
      <a href="../index.php" target="_blank" rel="noopener">Lihat Beranda Toko ↗</a>
      <a href="logout.php">Keluar</a>
    </div>
  </div>
</div>

<?php admin_subnav($aktif);
}

function admin_kaki(): void
{
    echo "\n</body>\n</html>\n";
}
