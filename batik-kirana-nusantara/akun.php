<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth_pembeli.php';
$base_url = '';
$judul_halaman = 'Akun Saya';

$pembeli = pembeli_saat_ini($koneksi);
if (!$pembeli) {
    header('Location: login.php');
    exit;
}

$stmtPesanan = $koneksi->prepare(
    "SELECT id_pesanan, kode_pesanan, total_harga, status, metode_pembayaran, dibuat_pada
     FROM pesanan WHERE id_pembeli = ? ORDER BY dibuat_pada DESC"
);
$stmtPesanan->execute([$pembeli['id_pembeli']]);
$riwayatPesanan = $stmtPesanan->fetchAll(PDO::FETCH_ASSOC);

// ---------- Detail item pesanan (kalau salah satu di-klik "Lihat Detail") ----------
$idDetailLihat = isset($_GET['lihat']) ? (int)$_GET['lihat'] : 0;
$detailItems = [];
if ($idDetailLihat > 0) {
    // Pastikan pesanan yang dibuka memang milik pembeli ini, bukan pesanan orang lain
    $cekMilik = array_filter($riwayatPesanan, fn($p) => (int)$p['id_pesanan'] === $idDetailLihat);
    if (!empty($cekMilik)) {
        $stmtDetail = $koneksi->prepare("SELECT * FROM detail_pesanan WHERE id_pesanan = ?");
        $stmtDetail->execute([$idDetailLihat]);
        $detailItems = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
    }
}

$labelStatus = [
    'Menunggu Pembayaran' => 'status-pending',
    'Diproses'            => 'status-proses',
    'Dikirim'             => 'status-kirim',
    'Selesai'             => 'status-selesai',
    'Dibatalkan'          => 'status-batal',
];

$inisial = mb_strtoupper(mb_substr(trim($pembeli['nama_pembeli']), 0, 1));

require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:48px; padding-bottom:60px;">
  <div class="wrap" style="max-width:720px;">

    <div class="akun-profil-card">
      <div class="akun-profil-header">
        <div class="akun-avatar"><?= htmlspecialchars($inisial) ?></div>
        <div>
          <h2 class="akun-nama"><?= htmlspecialchars($pembeli['nama_pembeli']) ?></h2>
          <p class="akun-email"><?= htmlspecialchars($pembeli['email']) ?></p>
        </div>
      </div>

      <div class="akun-info-grid">
        <div class="akun-info-item">
          <span class="akun-info-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.68 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.32 1.85.55 2.81.68A2 2 0 0 1 22 16.92z"/></svg>
          </span>
          <div>
            <span class="akun-info-label">Telepon</span>
            <span class="akun-info-value"><?= htmlspecialchars($pembeli['telepon']) ?></span>
          </div>
        </div>
        <div class="akun-info-item">
          <span class="akun-info-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
          </span>
          <div>
            <span class="akun-info-label">Alamat</span>
            <span class="akun-info-value"><?= htmlspecialchars($pembeli['alamat']) ?></span>
          </div>
        </div>
      </div>

      <p class="akun-info-note">Data ini otomatis dipakai saat kamu checkout — tidak perlu isi ulang.</p>

      <div class="akun-aksi">
        <a href="produk.php" class="btn btn-gold">Mulai Belanja</a>
        <a href="logout_pembeli.php" class="btn btn-outline-terang">Keluar</a>
      </div>
    </div>

    <h3 class="akun-riwayat-judul">Riwayat Pemesanan</h3>

    <?php if (empty($riwayatPesanan)): ?>
      <div class="empty-state">Kamu belum pernah membuat pesanan.</div>
    <?php else: ?>
      <div class="riwayat-list">
        <?php foreach ($riwayatPesanan as $p): ?>
          <div class="riwayat-card <?= $labelStatus[$p['status']] ?? '' ?>">
            <div class="riwayat-card-atas">
              <div>
                <span class="riwayat-kode"><?= htmlspecialchars($p['kode_pesanan']) ?></span>
                <span class="riwayat-tanggal"><?= date('d M Y, H:i', strtotime($p['dibuat_pada'])) ?></span>
              </div>
              <span class="riwayat-status-badge"><?= htmlspecialchars($p['status']) ?></span>
            </div>
            <div class="riwayat-card-bawah">
              <div>
                <span class="riwayat-metode">Metode: <?= htmlspecialchars($p['metode_pembayaran']) ?></span>
                <strong class="riwayat-total">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></strong>
              </div>
              <a href="akun.php?lihat=<?= (int)$p['id_pesanan'] ?>#detail-<?= (int)$p['id_pesanan'] ?>" class="riwayat-detail-toggle">
                <?= $idDetailLihat === (int)$p['id_pesanan'] ? 'Tutup Detail' : 'Lihat Detail' ?>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transform: rotate(<?= $idDetailLihat === (int)$p['id_pesanan'] ? '180deg' : '0deg' ?>);"><path d="m6 9 6 6 6-6"/></svg>
              </a>
            </div>

            <?php if ($idDetailLihat === (int)$p['id_pesanan']): ?>
              <div id="detail-<?= (int)$p['id_pesanan'] ?>" class="riwayat-detail">
                <?php foreach ($detailItems as $d): ?>
                  <div class="riwayat-item-row">
                    <span class="riwayat-item-nama"><?= htmlspecialchars($d['nama_produk']) ?> <span class="riwayat-item-jumlah">× <?= (int)$d['jumlah'] ?></span></span>
                    <span class="riwayat-item-subtotal">Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
