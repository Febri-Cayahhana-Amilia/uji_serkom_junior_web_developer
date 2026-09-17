<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/keranjang.php';
$base_url = '';
$judul_halaman = 'Checkout';

$keranjang = keranjang_isi($koneksi);
$error = '';
$pesananSukses = null;

if (empty($keranjang['items']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: keranjang.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $metode = trim($_POST['metode_pembayaran'] ?? '');

    // ---------- Validasi form (wajib) ----------
    if ($nama === '' || $email === '' || $telepon === '' || $alamat === '' || $metode === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (!preg_match('/^[0-9+\-\s]{8,20}$/', $telepon)) {
        $error = 'Format nomor telepon tidak valid.';
    } elseif (!in_array($metode, ['Transfer Bank', 'COD (Bayar di Tempat)', 'E-Wallet'], true)) {
        $error = 'Metode pembayaran tidak valid.';
    } elseif (empty($keranjang['items'])) {
        $error = 'Keranjang belanja kamu kosong.';
    } else {
        // ---------- Cek ulang stok sebelum transaksi ----------
        foreach ($keranjang['items'] as $item) {
            if ($item['jumlah'] > $item['stok']) {
                $error = 'Stok "' . $item['nama_produk'] . '" tidak mencukupi. Sisa stok: ' . $item['stok'] . '.';
                break;
            }
        }
    }

    if ($error === '') {
        try {
            $koneksi->beginTransaction();

            $kode_pesanan = 'BKN-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

            $stmtPesanan = $koneksi->prepare(
                "INSERT INTO pesanan (kode_pesanan, nama_pelanggan, email, telepon, alamat, metode_pembayaran, total_harga, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Menunggu Pembayaran') RETURNING id_pesanan"
            );
            $stmtPesanan->execute([$kode_pesanan, $nama, $email, $telepon, $alamat, $metode, $keranjang['total']]);
            $id_pesanan = $stmtPesanan->fetchColumn();

            $stmtDetail = $koneksi->prepare(
                "INSERT INTO detail_pesanan (id_pesanan, id_produk, nama_produk, harga_satuan, jumlah, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmtStok = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?");

            foreach ($keranjang['items'] as $item) {
                $stmtDetail->execute([
                    $id_pesanan, $item['id_produk'], $item['nama_produk'],
                    $item['harga'], $item['jumlah'], $item['subtotal'],
                ]);
                $stmtStok->execute([$item['jumlah'], $item['id_produk'], $item['jumlah']]);
                if ($stmtStok->rowCount() === 0) {
                    throw new Exception('Stok "' . $item['nama_produk'] . '" baru saja habis. Silakan coba lagi.');
                }
            }

            $koneksi->commit();
            keranjang_kosongkan();

            $pesananSukses = [
                'kode_pesanan' => $kode_pesanan,
                'nama'         => $nama,
                'total'        => $keranjang['total'],
                'metode'       => $metode,
                'items'        => $keranjang['items'],
            ];
        } catch (Exception $e) {
            $koneksi->rollBack();
            $error = 'Transaksi gagal: ' . $e->getMessage();
        }
    }
}

$val_nama = $_POST['nama_pelanggan'] ?? '';
$val_email = $_POST['email'] ?? '';
$val_telepon = $_POST['telepon'] ?? '';
$val_alamat = $_POST['alamat'] ?? '';
$val_metode = $_POST['metode_pembayaran'] ?? '';

require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:48px;">
  <div class="wrap">

    <?php if ($pesananSukses): ?>

      <div class="checkout-sukses">
        <h2 class="section-title">Pesanan Berhasil Dibuat 🎉</h2>
        <p class="alert alert-success">
          Terima kasih, <?= htmlspecialchars($pesananSukses['nama']) ?>! Kode pesananmu adalah
          <strong><?= htmlspecialchars($pesananSukses['kode_pesanan']) ?></strong>.
          Simpan kode ini sebagai bukti transaksi.
        </p>

        <table class="admin-table" style="margin-bottom:20px;">
          <thead><tr><th>Produk</th><th>Jumlah</th><th>Subtotal</th></tr></thead>
          <tbody>
            <?php foreach ($pesananSukses['items'] as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                <td><?= (int)$item['jumlah'] ?></td>
                <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <p><strong>Metode pembayaran:</strong> <?= htmlspecialchars($pesananSukses['metode']) ?></p>
        <p style="font-size:22px; margin-bottom:28px;"><strong>Total: Rp <?= number_format($pesananSukses['total'], 0, ',', '.') ?></strong></p>

        <a href="produk.php" class="btn btn-gold">Belanja Lagi</a>
        <a href="index.php" class="btn btn-outline">Kembali ke Beranda</a>
      </div>

    <?php else: ?>

      <h2 class="section-title">Checkout</h2>
      <p class="section-sub">Lengkapi data pengiriman dan pilih metode pembayaran.</p>

      <?php if ($error): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <div class="checkout-grid">
        <form method="POST" action="checkout.php" class="form-card" id="form-checkout" novalidate>
          <div class="form-field">
            <label for="nama_pelanggan">Nama Lengkap</label>
            <input type="text" id="nama_pelanggan" name="nama_pelanggan" required value="<?= htmlspecialchars($val_nama) ?>">
            <small class="form-error" id="error-nama_pelanggan"></small>
          </div>
          <div class="form-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($val_email) ?>">
            <small class="form-error" id="error-email"></small>
          </div>
          <div class="form-field">
            <label for="telepon">Nomor Telepon / WhatsApp</label>
            <input type="text" id="telepon" name="telepon" required placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($val_telepon) ?>">
            <small class="form-error" id="error-telepon"></small>
          </div>
          <div class="form-field">
            <label for="alamat">Alamat Pengiriman</label>
            <textarea id="alamat" name="alamat" required><?= htmlspecialchars($val_alamat) ?></textarea>
            <small class="form-error" id="error-alamat"></small>
          </div>
          <div class="form-field">
            <label for="metode_pembayaran">Metode Pembayaran</label>
            <select id="metode_pembayaran" name="metode_pembayaran" required>
              <option value="">— Pilih metode —</option>
              <?php foreach (['Transfer Bank', 'COD (Bayar di Tempat)', 'E-Wallet'] as $opsi): ?>
                <option value="<?= $opsi ?>" <?= $val_metode === $opsi ? 'selected' : '' ?>><?= $opsi ?></option>
              <?php endforeach; ?>
            </select>
            <small class="form-error" id="error-metode_pembayaran"></small>
          </div>
          <button type="submit" class="btn btn-gold">Buat Pesanan</button>
        </form>

        <div class="checkout-ringkasan">
          <h3>Ringkasan Pesanan</h3>
          <ul class="checkout-item-list">
            <?php foreach ($keranjang['items'] as $item): ?>
              <li>
                <span><?= htmlspecialchars($item['nama_produk']) ?> × <?= (int)$item['jumlah'] ?></span>
                <span>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
          <div class="checkout-total-row">
            <span>Total</span>
            <strong>Rp <?= number_format($keranjang['total'], 0, ',', '.') ?></strong>
          </div>
          <a href="keranjang.php" style="font-size:14px; color:var(--soga);">← Ubah keranjang</a>
        </div>
      </div>

    <?php endif; ?>
  </div>
</section>

<?php if (!$pesananSukses): ?>
<!-- ---------- Modal konfirmasi checkout ---------- -->
<div id="modal-konfirmasi" class="modal-overlay" hidden>
  <div class="modal-box">
    <h3>Konfirmasi Pesanan</h3>
    <p>Cek dulu data di bawah ini sebelum pesanan dibuat — pesanan tidak bisa diubah setelah dikirim.</p>
    <ul class="modal-ringkasan">
      <li><span>Nama</span><strong id="modal-nama"></strong></li>
      <li><span>Email</span><strong id="modal-email"></strong></li>
      <li><span>Telepon</span><strong id="modal-telepon"></strong></li>
      <li><span>Alamat</span><strong id="modal-alamat"></strong></li>
      <li><span>Metode Pembayaran</span><strong id="modal-metode"></strong></li>
      <li class="modal-ringkasan-total"><span>Total Bayar</span><strong>Rp <?= number_format($keranjang['total'], 0, ',', '.') ?></strong></li>
    </ul>
    <div class="modal-actions">
      <button type="button" class="btn btn-outline" id="modal-batal">Batal, Cek Lagi</button>
      <button type="button" class="btn btn-gold" id="modal-konfirmasi-btn">Ya, Buat Pesanan</button>
    </div>
  </div>
</div>

<script>
(() => {
  const form = document.getElementById('form-checkout');
  if (!form) return;

  const modal = document.getElementById('modal-konfirmasi');
  const btnBatal = document.getElementById('modal-batal');
  const btnKonfirmasi = document.getElementById('modal-konfirmasi-btn');
  let sudahDikonfirmasi = false;

  const tampilkanError = (idField, pesan) => {
    const el = document.getElementById('error-' + idField);
    if (el) el.textContent = pesan;
    const input = document.getElementById(idField);
    if (input) input.classList.toggle('input-error', !!pesan);
  };

  const bersihkanSemuaError = () => {
    form.querySelectorAll('.form-error').forEach((el) => (el.textContent = ''));
    form.querySelectorAll('.input-error').forEach((el) => el.classList.remove('input-error'));
  };

  const validasiForm = () => {
    bersihkanSemuaError();
    let valid = true;
    let fieldFokusPertama = null;

    const tandaiSalah = (idField, pesan) => {
      tampilkanError(idField, pesan);
      valid = false;
      if (!fieldFokusPertama) fieldFokusPertama = document.getElementById(idField);
    };

    const nama = form.nama_pelanggan.value.trim();
    const email = form.email.value.trim();
    const telepon = form.telepon.value.trim();
    const alamat = form.alamat.value.trim();
    const metode = form.metode_pembayaran.value.trim();

    if (nama === '') tandaiSalah('nama_pelanggan', 'Nama lengkap wajib diisi.');
    if (email === '') {
      tandaiSalah('email', 'Email wajib diisi.');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      tandaiSalah('email', 'Format email tidak valid.');
    }
    if (telepon === '') {
      tandaiSalah('telepon', 'Nomor telepon wajib diisi.');
    } else if (!/^[0-9+\-\s]{8,20}$/.test(telepon)) {
      tandaiSalah('telepon', 'Format nomor telepon tidak valid (8-20 digit).');
    }
    if (alamat === '') tandaiSalah('alamat', 'Alamat pengiriman wajib diisi.');
    if (metode === '') tandaiSalah('metode_pembayaran', 'Pilih salah satu metode pembayaran.');

    if (!valid && fieldFokusPertama) {
      fieldFokusPertama.focus();
      fieldFokusPertama.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    return valid;
  };

  const isiRingkasanModal = () => {
    document.getElementById('modal-nama').textContent = form.nama_pelanggan.value.trim();
    document.getElementById('modal-email').textContent = form.email.value.trim();
    document.getElementById('modal-telepon').textContent = form.telepon.value.trim();
    document.getElementById('modal-alamat').textContent = form.alamat.value.trim();
    document.getElementById('modal-metode').textContent = form.metode_pembayaran.value.trim();
  };

  const bukaModal = () => {
    isiRingkasanModal();
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  };

  const tutupModal = () => {
    modal.hidden = true;
    document.body.style.overflow = '';
  };

  form.addEventListener('submit', (e) => {
    if (sudahDikonfirmasi) return; // sudah lewat modal, biarkan form terkirim
    e.preventDefault();
    if (validasiForm()) bukaModal();
  });

  btnBatal.addEventListener('click', tutupModal);

  modal.addEventListener('click', (e) => {
    if (e.target === modal) tutupModal();
  });

  btnKonfirmasi.addEventListener('click', () => {
    sudahDikonfirmasi = true;
    tutupModal();
    form.submit();
  });
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>