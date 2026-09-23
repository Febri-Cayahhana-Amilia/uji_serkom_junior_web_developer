<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth_pembeli.php';
$base_url = '';
$judul_halaman = 'Daftar Akun';

if (!empty($_SESSION['pembeli_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$val_nama = $val_email = $val_telepon = $val_alamat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val_nama = trim($_POST['nama_pembeli'] ?? '');
    $val_email = trim($_POST['email'] ?? '');
    $val_telepon = trim($_POST['telepon'] ?? '');
    $val_alamat = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    // ---------- Validasi form (wajib) ----------
    if ($val_nama === '' || $val_email === '' || $val_telepon === '' || $val_alamat === '' || $password === '' || $konfirmasi === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($val_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (!preg_match('/^[0-9+\-\s]{8,20}$/', $val_telepon)) {
        $error = 'Format nomor telepon tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $stmtCek = $koneksi->prepare("SELECT id_pembeli FROM pembeli WHERE email = ?");
        $stmtCek->execute([$val_email]);
        if ($stmtCek->fetch()) {
            $error = 'Email ini sudah terdaftar. Silakan login.';
        }
    }

    if ($error === '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare(
            "INSERT INTO pembeli (nama_pembeli, email, password, telepon, alamat)
             VALUES (?, ?, ?, ?, ?) RETURNING id_pembeli"
        );
        $stmt->execute([$val_nama, $val_email, $hash, $val_telepon, $val_alamat]);
        $id_pembeli = $stmt->fetchColumn();

        $_SESSION['pembeli_id'] = $id_pembeli;
        $_SESSION['pembeli_nama'] = $val_nama;

        $tujuan = $_GET['lanjut'] ?? 'index.php';
        $tujuan = in_array($tujuan, ['checkout.php', 'index.php'], true) ? $tujuan : 'index.php';
        header('Location: ' . $tujuan);
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:48px;">
  <div class="wrap" style="max-width:520px;">
    <h2 class="section-title">Daftar Akun</h2>
    <p class="section-sub">Simpan data pengirimanmu supaya checkout berikutnya lebih cepat — tinggal pilih metode pembayaran.</p>

    <?php if ($error): ?>
      <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="daftar.php<?= isset($_GET['lanjut']) ? '?lanjut=' . urlencode($_GET['lanjut']) : '' ?>" class="form-card">
      <div class="form-field">
        <label for="nama_pembeli">Nama Lengkap</label>
        <input type="text" id="nama_pembeli" name="nama_pembeli" required value="<?= htmlspecialchars($val_nama) ?>">
      </div>
      <div class="form-field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($val_email) ?>">
      </div>
      <div class="form-field">
        <label for="telepon">Nomor Telepon / WhatsApp</label>
        <input type="text" id="telepon" name="telepon" required placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($val_telepon) ?>">
      </div>
      <div class="form-field">
        <label for="alamat">Alamat Pengiriman</label>
        <textarea id="alamat" name="alamat" required><?= htmlspecialchars($val_alamat) ?></textarea>
      </div>
      <div class="form-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="6">
      </div>
      <div class="form-field">
        <label for="konfirmasi_password">Konfirmasi Password</label>
        <input type="password" id="konfirmasi_password" name="konfirmasi_password" required minlength="6">
      </div>
      <button type="submit" class="btn btn-gold" style="width:100%;">Daftar</button>
    </form>

    <p style="text-align:center; margin-top:20px;">
      Sudah punya akun?
      <a href="login.php<?= isset($_GET['lanjut']) ? '?lanjut=' . urlencode($_GET['lanjut']) : '' ?>" style="color:var(--soga);">Login di sini</a>
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
