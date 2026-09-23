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
$kodeProduk = 'BKN-' . str_pad((string)$produk['id_produk'], 4, '0', STR_PAD_LEFT);

// ---------- Jenis produk: kain per meter vs pakaian jadi (ukuran) ----------
// - Kain dijual per meter -> input "Panjang (meter)" + kalkulasi harga (dideteksi dari nama)
// - Ukuran (S/M/L/XL/XXL) -> diambil dari kolom ukuran_tersedia yang diisi admin lewat
//   Kelola Produk, BUKAN ditebak dari nama, supaya admin punya kendali penuh produk
//   mana saja yang butuh ukuran.
$nama_lower = mb_strtolower($produk['nama_produk']);

$produk_kain = str_contains($nama_lower, 'kain');

$daftar_ukuran = !empty($produk['ukuran_tersedia'])
    ? array_values(array_filter(array_map('trim', explode(',', $produk['ukuran_tersedia']))))
    : [];
$produk_baju = !$produk_kain && count($daftar_ukuran) > 0;

$satuan_label = $produk_kain ? 'meter' : 'pcs';

// Produk lain dari kategori yang sama, untuk bagian "Produk Terkait"
$stmtTerkait = $koneksi->prepare(
    "SELECT id_produk, nama_produk, motif, harga, stok, gambar
     FROM produk
     WHERE id_kategori = ? AND id_produk != ?
     ORDER BY nama_produk
     LIMIT 4"
);
$stmtTerkait->execute([$produk['id_kategori'], $produk['id_produk']]);
$produkTerkait = $stmtTerkait->fetchAll(PDO::FETCH_ASSOC);

$teks_detail = "Halo Batik Kirana, saya ingin bertanya / memesan produk berikut:\n\n"
             . "*Nama Produk:* " . $produk['nama_produk'] . "\n"
             . "*Motif:* " . $produk['motif'] . "\n"
             . "*Harga:* Rp " . number_format($produk['harga'], 0, ',', '.');

require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:40px;">
  <div class="wrap">

    <nav class="pd-breadcrumb" aria-label="Navigasi lokasi halaman">
      <a href="index.php">Beranda</a>
      <span>/</span>
      <a href="produk.php">Produk</a>
      <span>/</span>
      <a href="produk.php?kategori=<?= (int)$produk['id_kategori'] ?>"><?= htmlspecialchars($produk['nama_kategori']) ?></a>
      <span>/</span>
      <span class="pd-breadcrumb-current"><?= htmlspecialchars($produk['nama_produk']) ?></span>
    </nav>

    <div class="pd-card">
      <div class="pd-grid">

        <div class="pd-thumb">
          <?php if (!empty($produk['gambar']) && is_file(__DIR__ . '/uploads/produk/' . $produk['gambar'])): ?>
            <img src="uploads/produk/<?= htmlspecialchars($produk['gambar']) ?>" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
          <?php else: ?>
            <div class="thumb-placeholder"><span>Belum ada foto</span></div>
          <?php endif; ?>
          <?php if ((int)$produk['stok'] === 0): ?>
            <span class="badge-stok badge-habis">Stok Habis</span>
          <?php elseif ((int)$produk['stok'] <= 5): ?>
            <span class="badge-stok">Stok Terbatas</span>
          <?php endif; ?>
        </div>

        <div class="pd-info">
          <div class="pd-eyebrow">
            <span class="produk-kategori"><?= htmlspecialchars($produk['nama_kategori']) ?></span>
            <span class="pd-kode">#<?= htmlspecialchars($kodeProduk) ?></span>
          </div>

          <h1 class="pd-title"><?= htmlspecialchars($produk['nama_produk']) ?></h1>

          <div class="pd-price-row">
            <span class="pd-price">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></span>
            <span class="pd-price-unit">/ <?= $satuan_label ?></span>
          </div>

          <div class="pd-tags">
            <span class="pd-tag">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 12 22l-9-9V3h10l9 9a2 2 0 0 1 0 2.41Z"/><circle cx="7.5" cy="7.5" r="1.2"/></svg>
              Motif <?= htmlspecialchars($produk['motif']) ?>
            </span>
            <span class="pd-tag <?= (int)$produk['stok'] > 0 ? '' : 'pd-tag-habis' ?>">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
              <?= (int)$produk['stok'] > 0 ? (int)$produk['stok'] . ' stok tersedia' : 'Stok habis' ?>
            </span>
          </div>

          <p class="pd-desc"><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></p>

          <?php if (isset($_GET['ditambahkan'])): ?>
            <p class="alert alert-success">Produk berhasil ditambahkan ke keranjang. <a href="keranjang.php" style="text-decoration:underline;">Lihat keranjang</a></p>
          <?php endif; ?>

          <div class="pd-purchase-box">
            <?php if ((int)$produk['stok'] > 0): ?>
              <form method="POST" action="keranjang.php" class="pd-tambah-form" id="formTambahKeranjang">
                <input type="hidden" name="aksi" value="tambah">
                <input type="hidden" name="id_produk" value="<?= (int)$produk['id_produk'] ?>">
                <input type="hidden" name="kembali" value="produk_detail.php?id=<?= (int)$produk['id_produk'] ?>">

                <?php if ($produk_baju): ?>
                  <div class="pd-ukuran-field">
                    <label>Pilih Ukuran</label>
                    <div class="pd-ukuran-options">
                      <?php foreach ($daftar_ukuran as $i => $u): ?>
                        <label class="pd-ukuran-pill">
                          <input type="radio" name="ukuran" value="<?= $u ?>" <?= $i === 0 ? 'checked' : '' ?> required>
                          <span><?= $u ?></span>
                        </label>
                      <?php endforeach; ?>
                    </div>
                    <span class="pd-ukuran-hint">Panduan ukuran umum — pastikan sesuai kebutuhanmu sebelum checkout.</span>
                  </div>
                <?php endif; ?>

                <div class="pd-qty-field">
                  <label for="jumlah"><?= $produk_kain ? 'Panjang (meter)' : 'Jumlah' ?></label>
                  <input type="number" id="jumlah" name="jumlah" value="1" min="1" max="<?= (int)$produk['stok'] ?>"
                         step="1" class="keranjang-qty-input"
                         data-harga-satuan="<?= (float)$produk['harga'] ?>">
                  <?php if ($produk_kain): ?>
                    <span class="pd-qty-hint">Contoh: isi 2 kalau butuh 2 meter kain.</span>
                  <?php endif; ?>
                </div>

                <?php if ($produk_kain): ?>
                  <div class="pd-estimasi" id="pdEstimasi">
                    Estimasi harga: <strong>Rp <?= number_format($produk['harga'], 0, ',', '.') ?></strong>
                  </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-gold pd-btn-utama">Tambah ke Keranjang</button>
              </form>

              <?php if ($produk_kain): ?>
              <script>
                (function () {
                  var input = document.getElementById('jumlah');
                  var out = document.getElementById('pdEstimasi');
                  if (!input || !out) return;
                  var harga = parseFloat(input.getAttribute('data-harga-satuan')) || 0;
                  function format(n) {
                    return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                  }
                  function hitung() {
                    var panjang = parseInt(input.value, 10) || 0;
                    out.innerHTML = 'Estimasi harga: <strong>' + format(harga * panjang) + '</strong> <span class="pd-estimasi-rincian">(' + panjang + ' meter × ' + format(harga) + ')</span>';
                  }
                  input.addEventListener('input', hitung);
                  hitung();
                })();
              </script>
              <?php endif; ?>
            <?php else: ?>
              <button type="button" class="btn btn-gold pd-btn-utama" disabled>Stok Habis</button>
            <?php endif; ?>

            <div class="pd-cta-row">
              <a href="https://wa.me/6281334568278?text=<?= urlencode($teks_detail) ?>" target="_blank" class="btn btn-outline pd-btn-wa">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.68 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.32 1.85.55 2.81.68A2 2 0 0 1 22 16.92z"/></svg>
                Pesan via WhatsApp
              </a>
              <a href="kontak.php?pesan=<?= urlencode($teks_detail) ?>" class="btn btn-outline pd-btn-mail">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                Tanya via Form / Email
              </a>
            </div>
          </div>

          <div class="pd-trust-grid">
            <div class="pd-trust-item">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="7" width="15" height="13" rx="1"/><path d="M16 10h4l3 3v4h-7"/><circle cx="5.5" cy="20.5" r="1.5"/><circle cx="18.5" cy="20.5" r="1.5"/></svg>
              <div>
                <strong>Pengiriman</strong>
                <span>Dikirim setelah pembayaran dikonfirmasi</span>
              </div>
            </div>
            <div class="pd-trust-item">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
              <div>
                <strong>Pembayaran</strong>
                <span>Transfer Bank, E-Wallet, atau COD</span>
              </div>
            </div>
            <div class="pd-trust-item">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 6v6c0 5 4 8.5 9 10 5-1.5 9-5 9-10V6z"/></svg>
              <div>
                <strong>Keaslian</strong>
                <span>Karya perajin batik lokal</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <?php if (count($produkTerkait) > 0): ?>
      <div class="pd-terkait">
        <h2 class="section-title">Produk Terkait</h2>
        <p class="section-sub">Motif lain dari kategori <?= htmlspecialchars($produk['nama_kategori']) ?>.</p>
        <div class="produk-grid">
          <?php foreach ($produkTerkait as $row): ?>
            <div class="produk-card">
              <div class="produk-thumb">
                <?php if (!empty($row['gambar']) && is_file(__DIR__ . '/uploads/produk/' . $row['gambar'])): ?>
                  <img src="uploads/produk/<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama_produk']) ?>">
                <?php else: ?>
                  <div class="thumb-placeholder"><span>Belum ada foto</span></div>
                <?php endif; ?>
                <?php if ((int)$row['stok'] === 0): ?>
                  <span class="badge-stok badge-habis">Stok Habis</span>
                <?php endif; ?>
              </div>
              <div class="produk-info">
                <span class="produk-kategori"><?= htmlspecialchars($produk['nama_kategori']) ?></span>
                <h3><?= htmlspecialchars($row['nama_produk']) ?></h3>
                <span class="produk-harga">Rp <?= number_format($row['harga'], 0, ',', '.') ?></span>
                <a href="produk_detail.php?id=<?= (int)$row['id_produk'] ?>" class="btn btn-outline btn-kecil pd-btn-terkait">Lihat Detail</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>