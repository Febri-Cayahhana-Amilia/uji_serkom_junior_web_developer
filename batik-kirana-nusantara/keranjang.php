<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/keranjang.php';
$base_url = '';
$judul_halaman = 'Keranjang Belanja';

// Tangani aksi tambah / ubah jumlah / hapus (dikirim dari produk.php, produk_detail.php, atau form di halaman ini)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $pesan_status = '';

    if ($aksi === 'tambah' && $id_produk > 0) {
        $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));
        keranjang_tambah($id_produk, $jumlah);
        $pesan_status = 'ditambahkan';
    } elseif ($aksi === 'ubah' && $id_produk > 0) {
        $jumlah = (int)($_POST['jumlah'] ?? 0);
        keranjang_ubah_jumlah($id_produk, $jumlah);
    } elseif ($aksi === 'hapus' && $id_produk > 0) {
        keranjang_hapus($id_produk);
    } elseif ($aksi === 'kosongkan') {
        keranjang_kosongkan();
    }

    // Kalau form kirim tujuan "kembali" (dari produk.php/produk_detail.php), balik ke sana.
    // Hanya izinkan path lokal (bukan URL luar) untuk mencegah open-redirect.
    $kembali = $_POST['kembali'] ?? '';
    if ($kembali !== '' && !preg_match('#^(https?:)?//#i', $kembali)) {
        $pemisah = (strpos($kembali, '?') !== false) ? '&' : '?';
        $tujuan = $pesan_status !== '' ? $kembali . $pemisah . $pesan_status . '=1' : $kembali;
        header('Location: ' . $tujuan);
        exit;
    }

    header('Location: keranjang.php');
    exit;
}

$keranjang = keranjang_isi($koneksi);

require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:48px;">
  <div class="wrap">
    <h2 class="section-title">Keranjang Belanja</h2>
    <p class="section-sub">Periksa kembali produk pilihanmu sebelum lanjut ke pembayaran.</p>

    <?php if (empty($keranjang['items'])): ?>
      <div class="empty-state">
        Keranjang kamu masih kosong. <a href="produk.php" style="color:var(--soga); text-decoration:underline;">Yuk lihat-lihat produk</a>.
      </div>
    <?php else: ?>

      <div class="keranjang-table-wrap">
        <table class="admin-table keranjang-table">
          <thead>
            <tr>
              <th>Produk</th>
              <th>Harga</th>
              <th>Jumlah</th>
              <th>Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($keranjang['items'] as $item): ?>
              <tr>
                <td class="keranjang-produk-cell">
                  <?php if (!empty($item['gambar']) && is_file(__DIR__ . '/uploads/produk/' . $item['gambar'])): ?>
                    <img src="uploads/produk/<?= htmlspecialchars($item['gambar']) ?>" alt="" class="keranjang-thumb">
                  <?php endif; ?>
                  <span><?= htmlspecialchars($item['nama_produk']) ?></span>
                </td>
                <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                <td>
                  <form method="POST" action="keranjang.php" class="keranjang-qty-form">
                    <input type="hidden" name="aksi" value="ubah">
                    <input type="hidden" name="id_produk" value="<?= (int)$item['id_produk'] ?>">
                    <input type="number" name="jumlah" value="<?= (int)$item['jumlah'] ?>" min="1" max="<?= (int)$item['stok'] ?>" class="keranjang-qty-input">
                    <button type="submit" class="btn btn-outline btn-kecil">Perbarui</button>
                  </form>
                </td>
                <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                <td>
                  <form method="POST" action="keranjang.php">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id_produk" value="<?= (int)$item['id_produk'] ?>">
                    <button type="submit" class="link-hapus keranjang-hapus-btn">Hapus</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="keranjang-ringkasan">
        <form method="POST" action="keranjang.php">
          <input type="hidden" name="aksi" value="kosongkan">
          <button type="submit" class="btn btn-outline">Kosongkan Keranjang</button>
        </form>
        <div class="keranjang-total">
          <span>Total Belanja</span>
          <strong>Rp <?= number_format($keranjang['total'], 0, ',', '.') ?></strong>
        </div>
        <a href="checkout.php" class="btn btn-gold">Lanjut ke Checkout</a>
      </div>

    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>