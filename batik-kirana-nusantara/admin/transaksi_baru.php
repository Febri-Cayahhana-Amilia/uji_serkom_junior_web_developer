<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/metode_bayar.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_keranjang']) || !is_array($_SESSION['admin_keranjang'])) {
    $_SESSION['admin_keranjang'] = []; // [ id_produk => jumlah, ... ]
}

$error = '';
$sukses = '';

$metodePilihan = ['Tunai (Bayar Langsung di Toko)', 'Transfer Bank', 'COD (Bayar di Tempat)', 'E-Wallet'];
$statusPilihan = ['Menunggu Pembayaran', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'];

$produkSemua = $koneksi->query(
    "SELECT id_produk, nama_produk, harga, stok FROM produk ORDER BY nama_produk"
)->fetchAll(PDO::FETCH_ASSOC);
$produkById = [];
foreach ($produkSemua as $p) {
    $produkById[(int)$p['id_produk']] = $p;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // ---------- Susun keranjang transaksi (dikelola admin, bukan pelanggan) ----------
    if ($aksi === 'tambah_item') {
        $id_produk = (int)($_POST['id_produk'] ?? 0);
        $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));
        if (isset($produkById[$id_produk])) {
            $stokTersedia = (int)$produkById[$id_produk]['stok'];
            $jumlahSaatIni = $_SESSION['admin_keranjang'][$id_produk] ?? 0;
            $jumlahBaru = min($jumlahSaatIni + $jumlah, $stokTersedia);
            if ($jumlahBaru < 1) {
                $error = 'Stok produk tersebut sudah habis.';
            } else {
                $_SESSION['admin_keranjang'][$id_produk] = $jumlahBaru;
                $sukses = 'Produk ditambahkan ke daftar transaksi.';
            }
        } else {
            $error = 'Produk tidak ditemukan.';
        }
    } elseif ($aksi === 'ubah_item') {
        $id_produk = (int)($_POST['id_produk'] ?? 0);
        $jumlah = (int)($_POST['jumlah'] ?? 0);
        if ($jumlah <= 0) {
            unset($_SESSION['admin_keranjang'][$id_produk]);
        } elseif (isset($produkById[$id_produk])) {
            $stokTersedia = (int)$produkById[$id_produk]['stok'];
            $_SESSION['admin_keranjang'][$id_produk] = min($jumlah, $stokTersedia);
        }
    } elseif ($aksi === 'hapus_item') {
        $id_produk = (int)($_POST['id_produk'] ?? 0);
        unset($_SESSION['admin_keranjang'][$id_produk]);
    } elseif ($aksi === 'kosongkan') {
        $_SESSION['admin_keranjang'] = [];
    }

    // ---------- Finalisasi jadi transaksi tersimpan ----------
    elseif ($aksi === 'buat_transaksi') {
        $nama = trim($_POST['nama_pelanggan'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $metodeUtama = trim($_POST['metode_pembayaran'] ?? '');
        $metodeDetail = trim($_POST['metode_detail'] ?? '');
        $metode = $metodeUtama;
        $status = trim($_POST['status'] ?? '');
        $pembayaranRaw = trim($_POST['pembayaran'] ?? '');
        $pembayaran = (float)str_replace(['.', ','], ['', '.'], $pembayaranRaw);

        // ---------- Hitung total dulu (dibutuhkan untuk validasi pembayaran) ----------
        $total = 0;
        foreach ($_SESSION['admin_keranjang'] as $id_produk => $jumlah) {
            if (isset($produkById[$id_produk])) {
                $total += $jumlah * (float)$produkById[$id_produk]['harga'];
            }
        }

        // ---------- Validasi form (wajib) ----------
        if ($nama === '' || $email === '' || $telepon === '' || $alamat === '' || $metode === '' || $status === '') {
            $error = 'Semua kolom data pelanggan wajib diisi. Jika transaksi langsung di toko dan sebagian data tidak ada, isi dengan tanda "-".';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== '-') {
            $error = 'Format email tidak valid.';
        } elseif (!preg_match('/^[0-9+\-\s]{1,20}$/', $telepon) && $telepon !== '-') {
            $error = 'Format nomor telepon tidak valid.';
        } elseif (!in_array($metodeUtama, $metodePilihan, true)) {
            $error = 'Metode pembayaran tidak valid.';
        } elseif (($metode = metode_gabung($metodeUtama, $metodeDetail)) === null) {
            $error = 'Pilih bank / e-wallet yang dipakai untuk pembayaran.';
        } elseif (!in_array($status, $statusPilihan, true)) {
            $error = 'Status pesanan tidak valid.';
        } elseif (empty($_SESSION['admin_keranjang'])) {
            $error = 'Belum ada produk yang dipilih untuk transaksi ini.';
        } elseif ($pembayaranRaw === '' || $pembayaran <= 0) {
            $error = 'Jumlah uang pembayaran wajib diisi.';
        } elseif ($pembayaran < $total) {
            $error = 'Uang pembayaran (Rp ' . number_format($pembayaran, 0, ',', '.') . ') kurang dari total belanja (Rp ' . number_format($total, 0, ',', '.') . '). Transaksi tidak dapat diproses.';
        } else {
            // ---------- Cek ulang stok sebelum transaksi ----------
            foreach ($_SESSION['admin_keranjang'] as $id_produk => $jumlah) {
                if (!isset($produkById[$id_produk]) || $jumlah > (int)$produkById[$id_produk]['stok']) {
                    $error = 'Stok salah satu produk sudah berubah/tidak mencukupi. Silakan periksa kembali daftar transaksi.';
                    break;
                }
            }
        }

        if ($error === '') {
            try {
                $koneksi->beginTransaction();

                $kembalian = $pembayaran - $total;

                $kode_pesanan = 'BKN-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

                $stmtPesanan = $koneksi->prepare(
                    "INSERT INTO pesanan (kode_pesanan, nama_pelanggan, email, telepon, alamat, metode_pembayaran, total_harga, status, uang_diterima, kembalian)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id_pesanan"
                );
                $stmtPesanan->execute([$kode_pesanan, $nama, $email, $telepon, $alamat, $metode, $total, $status, $pembayaran, $kembalian]);
                $id_pesanan = $stmtPesanan->fetchColumn();

                $stmtDetail = $koneksi->prepare(
                    "INSERT INTO detail_pesanan (id_pesanan, id_produk, nama_produk, harga_satuan, jumlah, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmtStok = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?");

                foreach ($_SESSION['admin_keranjang'] as $id_produk => $jumlah) {
                    $p = $produkById[$id_produk];
                    $subtotal = $jumlah * (float)$p['harga'];
                    $stmtDetail->execute([$id_pesanan, $id_produk, $p['nama_produk'], $p['harga'], $jumlah, $subtotal]);
                    $stmtStok->execute([$jumlah, $id_produk, $jumlah]);
                    if ($stmtStok->rowCount() === 0) {
                        throw new Exception('Stok "' . $p['nama_produk'] . '" tidak lagi mencukupi.');
                    }
                }

                $koneksi->commit();
                $_SESSION['admin_keranjang'] = [];

                header('Location: struk.php?id=' . (int)$id_pesanan . '&baru=1');
                exit;
            } catch (Exception $e) {
                $koneksi->rollBack();
                $error = 'Transaksi gagal: ' . $e->getMessage();
            }
        }
    }
}

// ---------- Ambil ulang isi keranjang admin (data produk terbaru) untuk ditampilkan ----------
$itemKeranjang = [];
$totalKeranjang = 0;
foreach ($_SESSION['admin_keranjang'] as $id_produk => $jumlah) {
    if (!isset($produkById[$id_produk])) {
        unset($_SESSION['admin_keranjang'][$id_produk]);
        continue;
    }
    $p = $produkById[$id_produk];
    $subtotal = $jumlah * (float)$p['harga'];
    $totalKeranjang += $subtotal;
    $itemKeranjang[] = [
        'id_produk'   => $id_produk,
        'nama_produk' => $p['nama_produk'],
        'harga'       => (float)$p['harga'],
        'stok'        => (int)$p['stok'],
        'jumlah'      => $jumlah,
        'subtotal'    => $subtotal,
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transaksi Manual — Batik Kirana Nusantara</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-shell">

<div class="admin-topbar">
  <div class="wrap">
    <span>Masuk sebagai <b><?= htmlspecialchars($_SESSION['admin_username']) ?></b></span>
    <div class="admin-topbar-actions">
      <a href="../index.php" target="_blank" rel="noopener">Lihat Beranda Toko ↗</a>
      <a href="logout.php">Keluar</a>
    </div>
  </div>
</div>

<div class="wrap admin-subnav">
  <a href="dashboard.php">Dashboard</a>
  <a href="kategori.php">Kelola Kategori</a>
  <a href="pesanan.php">Pesanan Masuk</a>
  <a href="transaksi_baru.php" class="admin-subnav-aktif">Transaksi Manual</a>
  <a href="laporan.php">Laporan Penjualan</a>
</div>

<div class="wrap" style="padding-top:24px; padding-bottom:60px;">
  <h2 class="section-title" style="margin-top:0;">Buat Transaksi Manual</h2>
  <p class="section-sub">
    Untuk pesanan yang masuk lewat telepon, chat, atau pembelian langsung di toko —
    dicatat di sini sebagai transaksi resmi, tanpa perlu konfirmasi via WhatsApp atau email.
  </p>

  <?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <?php if ($sukses): ?><p class="alert alert-success"><?= htmlspecialchars($sukses) ?></p><?php endif; ?>

  <div class="checkout-grid">

    <div>
      <!-- ---------- Pilih produk untuk ditambahkan ke transaksi ---------- -->
      <form method="POST" action="transaksi_baru.php" class="form-card" style="margin-bottom:24px;">
        <input type="hidden" name="aksi" value="tambah_item">
        <div class="form-field">
          <label for="id_produk">Pilih Produk</label>
          <select id="id_produk" name="id_produk" required>
            <option value="">— Pilih produk —</option>
            <?php foreach ($produkSemua as $p): ?>
              <option value="<?= (int)$p['id_produk'] ?>" <?= (int)$p['stok'] <= 0 ? 'disabled' : '' ?>>
                <?= htmlspecialchars($p['nama_produk']) ?> — Rp <?= number_format($p['harga'], 0, ',', '.') ?>
                (stok: <?= (int)$p['stok'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="jumlah">Jumlah</label>
          <input type="number" id="jumlah" name="jumlah" min="1" value="1" required>
        </div>
        <button type="submit" class="btn btn-gold">+ Tambahkan ke Transaksi</button>
      </form>

      <!-- ---------- Daftar produk yang sudah dipilih (keranjang admin) ---------- -->
      <h3>Daftar Produk dalam Transaksi Ini</h3>
      <?php if (empty($itemKeranjang)): ?>
        <div class="empty-state">Belum ada produk dipilih. Tambahkan produk lewat form di atas.</div>
      <?php else: ?>
        <table class="admin-table" style="margin-bottom:16px;">
          <thead>
            <tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($itemKeranjang as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                <td>
                  <form method="POST" action="transaksi_baru.php" class="keranjang-qty-form">
                    <input type="hidden" name="aksi" value="ubah_item">
                    <input type="hidden" name="id_produk" value="<?= (int)$item['id_produk'] ?>">
                    <input type="number" name="jumlah" value="<?= (int)$item['jumlah'] ?>" min="1" max="<?= (int)$item['stok'] ?>" class="keranjang-qty-input">
                    <button type="submit" class="btn btn-outline btn-kecil">Perbarui</button>
                  </form>
                </td>
                <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                <td>
                  <form method="POST" action="transaksi_baru.php">
                    <input type="hidden" name="aksi" value="hapus_item">
                    <input type="hidden" name="id_produk" value="<?= (int)$item['id_produk'] ?>">
                    <button type="submit" class="link-hapus keranjang-hapus-btn">Hapus</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <form method="POST" action="transaksi_baru.php">
          <input type="hidden" name="aksi" value="kosongkan">
          <button type="submit" class="btn btn-outline">Kosongkan Daftar</button>
        </form>
      <?php endif; ?>
    </div>

    <!-- ---------- Data pelanggan + finalisasi transaksi ---------- -->
    <div class="checkout-ringkasan">
      <h3>Data Pelanggan &amp; Pembayaran</h3>
      <form method="POST" action="transaksi_baru.php">
        <input type="hidden" name="aksi" value="buat_transaksi">
        <div class="form-field">
          <label for="nama_pelanggan">Nama Pelanggan</label>
          <input type="text" id="nama_pelanggan" name="nama_pelanggan" required>
        </div>
        <div class="form-field">
          <label for="email">Email <span style="font-weight:400;">(isi "-" jika tidak ada)</span></label>
          <input type="text" id="email" name="email" required placeholder="contoh@email.com atau -">
        </div>
        <div class="form-field">
          <label for="telepon">Nomor Telepon <span style="font-weight:400;">(isi "-" jika tidak ada)</span></label>
          <input type="text" id="telepon" name="telepon" required placeholder="08xxxxxxxxxx atau -">
        </div>
        <div class="form-field">
          <label for="alamat">Alamat <span style="font-weight:400;">(isi "-" jika pembelian langsung di toko)</span></label>
          <textarea id="alamat" name="alamat" required></textarea>
        </div>
        <div class="form-field">
          <label for="metode_pembayaran">Metode Pembayaran</label>
          <select id="metode_pembayaran" name="metode_pembayaran" required>
            <option value="">— Pilih metode —</option>
            <?php foreach ($metodePilihan as $opsi): ?>
              <option value="<?= $opsi ?>"><?= $opsi ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php metode_render_sub('metode_pembayaran', $_POST['metode_detail'] ?? ''); ?>
        <div class="form-field">
          <label for="status">Status Pesanan</label>
          <select id="status" name="status" required>
            <?php foreach ($statusPilihan as $s): ?>
              <option value="<?= $s ?>" <?= $s === 'Selesai' ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-field">
          <label for="pembayaran">Uang Pembayaran (Rp)</label>
          <input type="number" id="pembayaran" name="pembayaran" min="0" step="500"
                 value="<?= htmlspecialchars($_POST['pembayaran'] ?? '') ?>"
                 data-total="<?= (int)$totalKeranjang ?>" required>
        </div>

        <div class="checkout-total-row">
          <span>Total Belanja</span>
          <strong>Rp <?= number_format($totalKeranjang, 0, ',', '.') ?></strong>
        </div>
        <div class="checkout-total-row" id="baris-kembalian" style="font-size:14.5px;">
          <span>Kembalian</span>
          <strong id="nilai-kembalian">Rp 0</strong>
        </div>

        <button type="submit" class="btn btn-gold" style="width:100%; margin-top:16px;" <?= empty($itemKeranjang) ? 'disabled' : '' ?>>
          Buat Transaksi &amp; Cetak Struk
        </button>
        <script>
        (() => {
          const inputBayar = document.getElementById('pembayaran');
          const labelKembalian = document.getElementById('nilai-kembalian');
          if (!inputBayar || !labelKembalian) return;
          const total = parseFloat(inputBayar.dataset.total || '0');
          const formatRp = (n) => 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
          const perbarui = () => {
            const bayar = parseFloat(inputBayar.value || '0');
            const kembali = bayar - total;
            labelKembalian.textContent = formatRp(Math.max(kembali, 0));
            labelKembalian.style.color = (bayar > 0 && bayar < total) ? '#A3402C' : '';
          };
          inputBayar.addEventListener('input', perbarui);
          perbarui();
        })();
        </script>
      </form>
    </div>

  </div>
</div>

</body>
</html>