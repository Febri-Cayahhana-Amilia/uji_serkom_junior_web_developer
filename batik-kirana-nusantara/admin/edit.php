<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/upload_gambar.php';

$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

$kategoriList = $koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $koneksi->prepare("SELECT * FROM produk WHERE id_produk = ?");
$stmt->execute([$id_produk]);
$produk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produk) {
    header('Location: dashboard.php');
    exit;
}

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
            // Kalau tidak upload foto baru, pertahankan foto lama
            $nama_file_gambar = $hasilUpload['nama_file'] ?? $produk['gambar'];

            $stmt = $koneksi->prepare(
                "UPDATE produk SET id_kategori=?, nama_produk=?, deskripsi=?, motif=?, harga=?, stok=?, gambar=? WHERE id_produk=?"
            );
            if ($stmt->execute([$id_kategori, $nama_produk, $deskripsi, $motif, $harga, $stok, $nama_file_gambar, $id_produk])) {
                // Hapus file foto lama dari server kalau diganti dengan yang baru
                if (isset($hasilUpload['nama_file']) && $produk['gambar']) {
                    $fileLama = __DIR__ . '/../uploads/produk/' . $produk['gambar'];
                    if (is_file($fileLama)) @unlink($fileLama);
                }
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Gagal memperbarui produk.';
            }
        }
    }
} else {
    // Isi form dengan data lama saat pertama dibuka
    $_POST = $produk;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Produk — Admin</title>
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
  <h2 class="section-title">Edit Produk</h2>

  <div class="form-card" style="max-width:none;">
    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= (int)$id_produk ?>" enctype="multipart/form-data">
      <div class="form-field">
        <label for="nama_produk">Nama Produk</label>
        <input type="text" id="nama_produk" name="nama_produk" value="<?= htmlspecialchars($_POST['nama_produk'] ?? '') ?>" required>
      </div>
      <div class="form-field">
        <label for="id_kategori">Kategori</label>
        <select id="id_kategori" name="id_kategori" required>
          <?php foreach ($kategoriList as $kat): ?>
            <option value="<?= (int)$kat['id_kategori'] ?>" <?= (int)$_POST['id_kategori'] === (int)$kat['id_kategori'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($kat['nama_kategori']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-field">
        <label for="motif">Motif</label>
        <input type="text" id="motif" name="motif" value="<?= htmlspecialchars($_POST['motif'] ?? '') ?>">
      </div>
      <div class="form-field">
        <label for="harga">Harga (Rp)</label>
        <input type="number" id="harga" name="harga" min="0" step="1000" value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>" required>
      </div>
      <div class="form-field">
        <label for="stok">Stok</label>
        <input type="number" id="stok" name="stok" min="0" value="<?= htmlspecialchars($_POST['stok'] ?? '') ?>" required>
      </div>
      <div class="form-field">
        <label for="deskripsi">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
      </div>
      <div class="form-field">
        <label for="gambar">Foto Produk</label>
        <?php if (!empty($produk['gambar']) && is_file(__DIR__ . '/../uploads/produk/' . $produk['gambar'])): ?>
          <img src="../uploads/produk/<?= htmlspecialchars($produk['gambar']) ?>" alt="Foto saat ini"
               style="width:120px; height:120px; object-fit:cover; border-radius:6px; margin-bottom:10px; display:block; border:1px solid rgba(43,33,24,0.15);">
        <?php else: ?>
          <p style="font-size:13.5px; color:var(--soga); margin:0 0 10px;">Belum ada foto untuk produk ini.</p>
        <?php endif; ?>
        <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp">
        <p style="font-size:12.5px; color:var(--soga); margin:6px 0 0;">Kosongkan kalau tidak ingin mengganti foto.</p>
      </div>
      <button type="submit" class="btn btn-gold">Simpan Perubahan</button>
      <a href="dashboard.php" class="btn btn-outline" style="color:var(--indigo); border-color:var(--indigo);">Batal</a>
    </form>
  </div>
</div>

</body>
</html>
