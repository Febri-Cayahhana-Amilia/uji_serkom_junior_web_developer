<?php
require __DIR__ . '/../includes/auth_admin.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/admin_layout.php';

$cari = trim($_GET['cari'] ?? '');
$idKategori = max(0, (int)($_GET['kategori'] ?? 0));

$kategoriList = $koneksi->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);

$where = [];
$params = [];
if ($cari !== '') {
    $where[] = "p.nama_produk ILIKE ?";
    $params[] = '%' . $cari . '%';
}
if ($idKategori > 0) {
    $where[] = "p.id_kategori = ?";
    $params[] = $idKategori;
}
$sql = "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar, p.ukuran_tersedia, k.nama_kategori
        FROM produk p LEFT JOIN kategori k ON p.id_kategori = k.id_kategori"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY p.id_produk DESC";
$stmt = $koneksi->prepare($sql);
$stmt->execute($params);
$produkList = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_kepala('Kelola Produk', 'produk');
?>

<div class="wrap" style="padding-top:24px; padding-bottom:60px;">
  <div class="admin-toolbar">
    <h2 class="section-title" style="margin:0;">Kelola Produk</h2>
    <a href="tambah.php" class="btn btn-gold">+ Tambah Produk</a>
  </div>

  <?php if (!empty($_GET['diperbarui'])): ?><p class="alert alert-success">Produk berhasil diperbarui.</p><?php endif; ?>
  <?php if (!empty($_GET['ditambah'])): ?><p class="alert alert-success">Produk berhasil ditambahkan.</p><?php endif; ?>
  <?php if (!empty($_GET['dihapus'])): ?><p class="alert alert-success">Produk berhasil dihapus.</p><?php endif; ?>
  <?php if (!empty($_GET['gagal'])): ?>
    <p class="alert alert-error">Produk tidak bisa dihapus karena sudah tercatat di transaksi. Ubah stoknya menjadi 0 lewat tombol Edit agar tidak tampil sebagai tersedia.</p>
  <?php endif; ?>

  <form method="GET" action="produk.php" class="filter-bar">
    <div class="form-field">
      <label for="cari">Cari Produk</label>
      <input type="search" id="cari" name="cari" value="<?= h($cari) ?>" placeholder="Nama produk">
    </div>
    <div class="form-field">
      <label for="kategori">Kategori</label>
      <select id="kategori" name="kategori">
        <option value="0">Semua kategori</option>
        <?php foreach ($kategoriList as $k): ?>
          <option value="<?= (int)$k['id_kategori'] ?>" <?= $idKategori === (int)$k['id_kategori'] ? 'selected' : '' ?>><?= h($k['nama_kategori']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-gold">Terapkan</button>
    <?php if ($cari !== '' || $idKategori > 0): ?><a href="produk.php" class="btn btn-outline">Reset</a><?php endif; ?>
  </form>

  <div class="tabel-gulir">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Foto</th><th>Nama Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Ukuran</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($produkList) > 0): ?>
        <?php foreach ($produkList as $row): ?>
          <tr>
            <td>
              <?php if (!empty($row['gambar']) && is_file(__DIR__ . '/../uploads/produk/' . $row['gambar'])): ?>
                <img src="../uploads/produk/<?= h($row['gambar']) ?>" alt=""
                     style="width:48px; height:48px; object-fit:cover; border-radius:6px; display:block;">
              <?php else: ?>
                <span style="font-size:12px; color:#A3402C;">Belum ada foto</span>
              <?php endif; ?>
            </td>
            <td><?= h($row['nama_produk']) ?></td>
            <td><?= h($row['nama_kategori'] ?? '—') ?></td>
            <td><?= fmt_rupiah($row['harga']) ?></td>
            <td>
              <?php $stok = (int)$row['stok']; ?>
              <?php if ($stok === 0): ?><span class="badge badge-batal">Habis</span>
              <?php elseif ($stok <= 5): ?><span class="badge badge-stok-rendah"><?= $stok ?> (menipis)</span>
              <?php else: ?><?= $stok ?><?php endif; ?>
            </td>
            <td>
              <?php if (!empty($row['ukuran_tersedia'])): ?>
                <span style="font-size:12.5px; color:var(--indigo);"><?= h($row['ukuran_tersedia']) ?></span>
              <?php else: ?>
                <span style="font-size:12.5px; color:var(--soga);">—</span>
              <?php endif; ?>
            </td>
            <td class="admin-actions">
              <a href="edit.php?id=<?= (int)$row['id_produk'] ?>" class="link-edit">Edit</a>
              <form method="POST" action="hapus.php" onsubmit="return confirm('Yakin ingin menghapus produk ini?');" style="display:inline;">
                <input type="hidden" name="id" value="<?= (int)$row['id_produk'] ?>">
                <button type="submit" class="link-hapus keranjang-hapus-btn">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7">Tidak ada produk yang cocok.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
  <p style="font-size:13.5px; color:var(--soga); margin-top:10px;"><?= count($produkList) ?> produk ditampilkan.</p>
</div>

<?php admin_kaki();
