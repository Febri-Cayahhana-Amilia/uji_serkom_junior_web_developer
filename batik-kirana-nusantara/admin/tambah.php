<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/upload_gambar.php';

$error = '';
$kategoriList = $koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $motif = trim($_POST['motif'] ?? '');
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama_produk === '' || $id_kategori === 0 || $harga <= 0) {
        $error = 'Nama produk, kategori, dan harga wajib diisi dengan benar.';
    } else {
        $hasilUpload = proses_upload_gambar('gambar');

        if ($hasilUpload && isset($hasilUpload['error'])) {
            $error = $hasilUpload['error'];
        } else {
            $nama_file_gambar = $hasilUpload['nama_file'] ?? null;

            $stmt = $koneksi->prepare(
                "INSERT INTO produk (id_kategori, nama_produk, deskripsi, motif, harga, stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            if ($stmt->execute([$id_kategori, $nama_produk, $deskripsi, $motif, $harga, $stok, $nama_file_gambar])) {
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Gagal menyimpan produk.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Produk — Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-shell">

<div class="admin-topbar">
  <div class="wrap">
    <span>Masuk sebagai <b><?= htmlspecialchars($_SESSION['admin_username']) ?></b></span>
    <a href="logout.php">Keluar</a>
  </div>
</div>

<div class="wrap" style="padding-top:40px; padding-bottom:60px; max-width:640px;">
  <h2 class="section-title">Tambah Produk</h2>

  <div class="form-card" style="max-width:none;">
    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="tambah.php" enctype="multipart/form-data">
      <div class="form-field">
        <label for="nama_produk">Nama Produk</label>
        <input type="text" id="nama_produk" name="nama_produk" required>
      </div>
      <div class="form-field">
        <label for="id_kategori">Kategori</label>
        <select id="id_kategori" name="id_kategori" required>
          <option value="">— Pilih kategori —</option>
          <?php foreach ($kategoriList as $kat): ?>
            <option value="<?= (int)$kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-field">
        <label for="motif">Motif</label>
        <input type="text" id="motif" name="motif">
      </div>
      <div class="form-field">
        <label for="harga">Harga (Rp)</label>
        <input type="number" id="harga" name="harga" min="0" step="1000" required>
      </div>
      <div class="form-field">
        <label for="stok">Stok</label>
        <input type="number" id="stok" name="stok" min="0" required>
      </div>
      <div class="form-field">
        <label for="deskripsi">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi"></textarea>
      </div>
      <div class="form-field">
        <label for="gambar">Foto Produk (asli, JPG/PNG/WEBP, maks 2MB)</label>
        <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp">
      </div>
      <button type="submit" class="btn btn-gold">Simpan Produk</button>
      <a href="dashboard.php" class="btn btn-outline" style="color:var(--indigo); border-color:var(--indigo);">Batal</a>
    </form>
  </div>
</div>

</body>
</html>
