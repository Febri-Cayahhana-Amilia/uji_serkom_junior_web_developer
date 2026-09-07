<?php
require __DIR__ . '/includes/db.php';
$base_url = '';

$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $koneksi->prepare(
    "SELECT p.*, k.nama_kategori
     FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori
     WHERE p.id_produk = ?"
);
$stmt->execute([$id_produk]);
$produk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produk) {
    header('Location: produk.php');
    exit;
}

$judul_halaman = $produk['nama_produk'];


$teks_detail = "Halo Batik Kirana, saya ingin bertanya / memesan produk berikut:\n\n"
             . "*Nama Produk:* " . $produk['nama_produk'] . "\n"
             . "*Motif:* " . $produk['motif'] . "\n"
             . "*Harga:* Rp " . number_format($produk['harga'], 0, ',', '.');

require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:48px;">
  <div class="wrap">
    <p style="margin-bottom:28px;"><a href="produk.php" style="color:var(--soga);">← Kembali ke Produk</a></p>

    <div class="detail-grid">
      <div class="detail-thumb">
        <?php if (!empty($produk['gambar']) && is_file(__DIR__ . '/uploads/produk/' . $produk['gambar'])): ?>
          <img src="uploads/produk/<?= htmlspecialchars($produk['gambar']) ?>" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
        <?php else: ?>
          <div class="thumb-placeholder"><span>Belum ada foto</span></div>
        <?php endif; ?>
      </div>

      <div class="detail-info">
        <span class="produk-kategori"><?= htmlspecialchars($produk['nama_kategori']) ?></span>
        <h1><?= htmlspecialchars($produk['nama_produk']) ?></h1>
        <p class="detail-harga">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></p>

        <div class="detail-meta">
          <span>Motif: <?= htmlspecialchars($produk['motif']) ?></span>
          <span><?= (int)$produk['stok'] > 0 ? (int)$produk['stok'] . ' stok tersedia' : 'Stok habis' ?></span>
        </div>

        <p class="detail-desc"><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></p>

        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px;">
          <!-- Tombol WhatsApp dengan Detail Produk -->
          <a href="https://wa.me/6281334568278?text=<?= urlencode($teks_detail) ?>" target="_blank" class="btn btn-gold">
            Pesan via WhatsApp
          </a>

          <!-- Tombol Form Email dengan Detail Produk -->
          <a href="kontak.php?pesan=<?= urlencode($teks_detail) ?>" class="btn btn-outline" style="border-color: var(--indigo); color: var(--indigo);">
            Tanya via Form / Email
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>