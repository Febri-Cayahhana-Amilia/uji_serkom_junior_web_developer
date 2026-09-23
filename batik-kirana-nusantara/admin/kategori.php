<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';

$error = '';
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        if ($nama_kategori === '') {
            $error = 'Nama kategori wajib diisi.';
        } else {
            try {
                $stmt = $koneksi->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
                $stmt->execute([$nama_kategori]);
                $sukses = 'Kategori "' . $nama_kategori . '" berhasil ditambahkan.';
            } catch (PDOException $e) {
                $error = 'Kategori dengan nama tersebut sudah ada.';
            }
        }
    } elseif ($aksi === 'ubah') {
        $id_kategori = (int)($_POST['id_kategori'] ?? 0);
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        if ($nama_kategori === '') {
            $error = 'Nama kategori wajib diisi.';
        } elseif ($id_kategori <= 0) {
            $error = 'Kategori tidak ditemukan.';
        } else {
            try {
                $stmt = $koneksi->prepare("UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?");
                $stmt->execute([$nama_kategori, $id_kategori]);
                $sukses = 'Kategori berhasil diperbarui menjadi "' . $nama_kategori . '".';
            } catch (PDOException $e) {
                $error = 'Kategori dengan nama tersebut sudah ada.';
            }
        }
    } elseif ($aksi === 'hapus') {
        $id_kategori = (int)($_POST['id_kategori'] ?? 0);
        $cekProduk = $koneksi->prepare("SELECT COUNT(*) FROM produk WHERE id_kategori = ?");
        $cekProduk->execute([$id_kategori]);
        if ($cekProduk->fetchColumn() > 0) {
            $error = 'Kategori tidak bisa dihapus karena masih dipakai oleh produk.';
        } else {
            $stmt = $koneksi->prepare("DELETE FROM kategori WHERE id_kategori = ?");
            $stmt->execute([$id_kategori]);
            $sukses = 'Kategori berhasil dihapus.';
        }
    }
}

$kategoriList = $koneksi->query(
    "SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS jumlah_produk
     FROM kategori k LEFT JOIN produk p ON p.id_kategori = k.id_kategori
     GROUP BY k.id_kategori, k.nama_kategori
     ORDER BY k.nama_kategori"
)->fetchAll(PDO::FETCH_ASSOC);

// ---------- Ambil data kategori yang sedang diedit (jika ada) ----------
$idEdit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$kategoriEdit = null;
if ($idEdit > 0) {
    $stmtEdit = $koneksi->prepare("SELECT id_kategori, nama_kategori FROM kategori WHERE id_kategori = ?");
    $stmtEdit->execute([$idEdit]);
    $kategoriEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Kategori — Batik Kirana Nusantara</title>
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
  <a href="dashboard.php">Kelola Produk</a>
  <a href="kategori.php" class="admin-subnav-aktif">Kelola Kategori</a>
  <a href="pesanan.php">Pesanan Masuk</a>
  <a href="transaksi_baru.php">Transaksi Manual</a>
  <a href="laporan.php">Laporan Penjualan</a>
</div>

<div class="wrap" style="padding-top:24px; padding-bottom:60px;">
  <h2 class="section-title" style="margin-top:0;">Kelola Kategori</h2>

  <?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <?php if ($sukses): ?><p class="alert alert-success"><?= htmlspecialchars($sukses) ?></p><?php endif; ?>

  <?php if ($kategoriEdit): ?>
    <form method="POST" action="kategori.php" class="form-card" style="margin-bottom:32px;">
      <input type="hidden" name="aksi" value="ubah">
      <input type="hidden" name="id_kategori" value="<?= (int)$kategoriEdit['id_kategori'] ?>">
      <div class="form-field">
        <label for="nama_kategori">Ubah Nama Kategori</label>
        <input type="text" id="nama_kategori" name="nama_kategori" required value="<?= htmlspecialchars($kategoriEdit['nama_kategori']) ?>">
      </div>
      <button type="submit" class="btn btn-gold">Simpan Perubahan</button>
      <a href="kategori.php" class="btn btn-outline">Batal</a>
    </form>
  <?php else: ?>
    <form method="POST" action="kategori.php" class="form-card" style="margin-bottom:32px;">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-field">
        <label for="nama_kategori">Nama Kategori Baru</label>
        <input type="text" id="nama_kategori" name="nama_kategori" required placeholder="Contoh: Syal Batik">
      </div>
      <button type="submit" class="btn btn-gold">+ Tambah Kategori</button>
    </form>
  <?php endif; ?>

  <table class="admin-table">
    <thead>
      <tr><th>Nama Kategori</th><th>Jumlah Produk</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      <?php if (count($kategoriList) > 0): ?>
        <?php foreach ($kategoriList as $kat): ?>
          <tr>
            <td><?= htmlspecialchars($kat['nama_kategori']) ?></td>
            <td><?= (int)$kat['jumlah_produk'] ?></td>
            <td class="admin-actions">
              <a href="kategori.php?edit=<?= (int)$kat['id_kategori'] ?>" class="link-edit">Edit</a>
              <form method="POST" action="kategori.php" onsubmit="return confirm('Yakin ingin menghapus kategori ini?');" style="display:inline;">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id_kategori" value="<?= (int)$kat['id_kategori'] ?>">
                <button type="submit" class="link-hapus keranjang-hapus-btn">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="3">Belum ada kategori.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>