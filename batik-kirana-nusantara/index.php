<?php
require __DIR__ . '/includes/db.php';
$base_url = '';
$judul_halaman = 'Beranda';

// Ambil 3 produk terbaru untuk ditampilkan di beranda
$stmt = $koneksi->query(
    "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar, k.nama_kategori
     FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori
     ORDER BY p.dibuat_pada DESC LIMIT 3"
);
$unggulan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategori beserta jumlah produk di masing-masing, untuk showcase kategori
$kategoriShowcase = $koneksi->query(
    "SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS jumlah_produk
     FROM kategori k LEFT JOIN produk p ON p.id_kategori = k.id_kategori
     GROUP BY k.id_kategori, k.nama_kategori
     ORDER BY k.nama_kategori"
)->fetchAll(PDO::FETCH_ASSOC);

$ikon_kategori = [
    'Batik Tulis' => '&#10052;',
    'Batik Cap' => '&#9670;',
    'Aksesoris' => '&#10022;',
];

require __DIR__ . '/includes/header.php';
?>

<header class="hero" style="padding-bottom: 60px;">
  <div class="hero-pattern" aria-hidden="true"></div>
  <div class="wrap hero-grid">
    <div>
      <p class="hero-kicker">Karya Otentik Perajin Nusantara</p>
      <h1>Bukan Cuma Motif,<br>Ini Cerita Kamu.</h1>
      <p>Seni membatik tradisional yang kami hadirkan kembali dalam busana modern, nyaman untuk setiap momen harianmu.</p>
      <div style="display:flex; gap:16px; flex-wrap:wrap; margin-top:24px;">
        <a href="produk.php" class="btn btn-gold">Lihat Semua Produk</a>
        <a href="tentang.php" class="btn btn-outline">Cerita Kami</a>
      </div>
    </div>
    <div class="hero-motif">
      <svg class="motif-svg" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="60" cy="60" r="34" fill="none" stroke="var(--gold)" stroke-width="1.4"/>
        <circle cx="60" cy="60" r="22" fill="none" stroke="var(--gold)" stroke-width="1.4"/>
        <g stroke="var(--gold)" stroke-width="1.4">
          <path d="M60 8 v20 M60 92 v20 M8 60 h20 M92 60 h20"/>
          <path d="M25 25 l14 14 M81 25 l-14 14 M25 95 l14-14 M81 95 l-14-14"/>
        </g>
        <circle cx="60" cy="60" r="4" fill="var(--gold)"/>
      </svg>
      <h3>Motif Minggu Ini: Sido Mukti</h3>
      <p>Melambangkan harapan hidup sejahtera dan sering dipakai dalam upacara adat pernikahan Jawa.</p>
    </div>
  </div>
</header>

<section class="section kategori-showcase">
  <div class="wrap">
    <h2 class="section-title">Jelajahi per Kategori</h2>
    <p class="section-sub">Tiga kategori utama koleksi kami — langsung ke yang kamu cari.</p>

    <div class="kategori-grid">
      <?php foreach ($kategoriShowcase as $i => $kat): ?>
        <a href="produk.php?kategori=<?= (int)$kat['id_kategori'] ?>" class="kategori-card reveal" style="--delay: <?= $i * 70 ?>ms;">
          <span class="kategori-icon"><?= $ikon_kategori[$kat['nama_kategori']] ?? '&#10022;' ?></span>
          <h3><?= htmlspecialchars($kat['nama_kategori']) ?></h3>
          <span class="kategori-count"><?= (int)$kat['jumlah_produk'] ?> produk</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="background:var(--panel);">
  <div class="wrap">
    <h2 class="section-title">Produk Terbaru</h2>
    <p class="section-sub">Diambil langsung dari katalog kami — tersedia dan siap dipesan.</p>

    <div class="produk-grid">
      <?php if (count($unggulan) > 0): ?>
        <?php foreach ($unggulan as $i => $row): ?>
          <div class="produk-card reveal" style="--delay: <?= $i * 60 ?>ms;">
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
              <span class="produk-stok"><?= (int)$row['stok'] ?> stok tersedia</span>
              <a href="produk_detail.php?id=<?= (int)$row['id_produk'] ?>" class="btn btn-gold">Lihat Detail</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">Belum ada produk. Tambahkan lewat panel admin.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="value-strip">
      <div class="value-item">
        <span class="value-num">01</span>
        <h3>Perajin Lokal</h3>
        <p>Dikerjakan tangan oleh perajin di sekitar kami.</p>
      </div>
      <div class="value-item">
        <span class="value-num">02</span>
        <h3>Pewarna Alami</h3>
        <p>Sebagian besar koleksi memakai pewarna dari tumbuhan.</p>
      </div>
      <div class="value-item">
        <span class="value-num">03</span>
        <h3>Motif Otentik</h3>
        <p>Setiap motif menjaga makna, bukan sekadar tren.</p>
      </div>
    </div>
  </div>
</section>

<section class="section langkah-section">
  <div class="wrap">
    <h2 class="section-title">Cara Memesan</h2>
    <p class="section-sub">Tiga langkah sederhana, tanpa ribet.</p>

    <div class="langkah-grid">
      <div class="langkah-item reveal" style="--delay:0ms;">
        <span class="langkah-num">1</span>
        <h3>Pilih Produk</h3>
        <p>Jelajahi katalog, lihat detail motif, harga, dan ketersediaan stok.</p>
      </div>
      <div class="langkah-item reveal" style="--delay:80ms;">
        <span class="langkah-num">2</span>
        <h3>Hubungi Kami</h3>
        <p>Kirim pesan lewat halaman Kontak dengan produk yang kamu mau.</p>
      </div>
      <div class="langkah-item reveal" style="--delay:160ms;">
        <span class="langkah-num">3</span>
        <h3>Konfirmasi &amp; Kirim</h3>
        <p>Kami balas via email untuk atur pembayaran dan pengiriman.</p>
      </div>
    </div>

    <div style="text-align:center; margin-top:36px;">
      <a href="kontak.php" class="btn btn-gold">Mulai Pesan Sekarang</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>