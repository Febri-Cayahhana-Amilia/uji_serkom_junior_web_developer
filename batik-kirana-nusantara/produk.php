<?php
require __DIR__ . '/includes/db.php';
$base_url = '';
$judul_halaman = 'Produk';

$id_kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$kata_kunci = trim($_GET['cari'] ?? '');

$kategoriList = $koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);

$kondisi = [];
$parameter = [];

if ($id_kategori > 0) {
    $kondisi[] = 'p.id_kategori = ?';
    $parameter[] = $id_kategori;
}

if ($kata_kunci !== '') {
    $kondisi[] = '(p.nama_produk ILIKE ? OR p.motif ILIKE ?)';
    $parameter[] = '%' . $kata_kunci . '%';
    $parameter[] = '%' . $kata_kunci . '%';
}

$sqlWhere = count($kondisi) > 0 ? 'WHERE ' . implode(' AND ', $kondisi) : '';

$stmt = $koneksi->prepare(
    "SELECT p.id_produk, p.nama_produk, p.motif, p.harga, p.stok, p.gambar, k.nama_kategori
     FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori
     $sqlWhere
     ORDER BY p.nama_produk"
);
$stmt->execute($parameter);
$produkList = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:48px;">
  <div class="wrap">
    <h2 class="section-title">Semua Produk</h2>
    <p class="section-sub">Jelajahi koleksi batik tulis, batik cap, dan aksesoris kami — setiap motif punya cerita.</p>

    <form method="GET" action="produk.php" class="cari-box">
      <?php if ($id_kategori > 0): ?>
        <input type="hidden" name="kategori" value="<?= (int)$id_kategori ?>">
      <?php endif; ?>
      <input type="text" name="cari" placeholder="Cari nama produk atau motif…" value="<?= htmlspecialchars($kata_kunci) ?>">
      <button type="submit" class="btn btn-gold">Cari</button>
    </form>

    <div class="kategori-strip">
      <a href="produk.php" class="kategori-chip <?= $id_kategori === 0 ? 'active' : '' ?>">Semua</a>
      <?php foreach ($kategoriList as $kat): ?>
        <a href="produk.php?kategori=<?= (int)$kat['id_kategori'] ?>"
           class="kategori-chip <?= $id_kategori === (int)$kat['id_kategori'] ? 'active' : '' ?>">
          <?= htmlspecialchars($kat['nama_kategori']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($kata_kunci !== ''): ?>
      <p class="hasil-cari-info">
        Menampilkan <?= count($produkList) ?> hasil untuk "<?= htmlspecialchars($kata_kunci) ?>"
        — <a href="produk.php<?= $id_kategori > 0 ? '?kategori=' . (int)$id_kategori : '' ?>">hapus pencarian</a>
      </p>
    <?php endif; ?>

    <div class="produk-grid">
      <?php if (count($produkList) > 0): ?>
        <?php foreach ($produkList as $i => $row): ?>
          <div class="produk-card reveal" style="--delay: <?= min($i, 8) * 50 ?>ms;">
            <div class="produk-thumb">
              <?php if (!empty($row['gambar']) && is_file(__DIR__ . '/uploads/produk/' . $row['gambar'])): ?>
                <img src="uploads/produk/<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama_produk']) ?>">
              <?php else: ?>
                <div class="thumb-placeholder"><span>Belum ada foto</span></div>
              <?php endif; ?>
              <?php if ((int)$row['stok'] > 0 && (int)$row['stok'] <= 5): ?>
                <span class="badge-stok">Stok Terbatas</span>
              <?php elseif ((int)$row['stok'] === 0): ?>
                <span class="badge-stok badge-habis">Stok Habis</span>
              <?php endif; ?>
            </div>
            <div class="produk-info">
              <span class="produk-kategori"><?= htmlspecialchars($row['nama_kategori']) ?></span>
              <h3><?= htmlspecialchars($row['nama_produk']) ?></h3>
              <span class="produk-harga">Rp <?= number_format($row['harga'], 0, ',', '.') ?></span>
              <span class="produk-stok"><?= (int)$row['stok'] > 0 ? (int)$row['stok'] . ' stok tersedia' : 'Stok habis' ?></span>
              <a href="produk_detail.php?id=<?= (int)$row['id_produk'] ?>" class="btn btn-gold">Lihat Detail</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">Tidak ada produk yang cocok. Coba kata kunci atau kategori lain.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
