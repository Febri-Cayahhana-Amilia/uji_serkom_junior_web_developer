<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth_pembeli.php';
$base_url = '';
$judul_halaman = 'Login';

if (!empty($_SESSION['pembeli_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$val_email = '';
$tujuan = $_GET['lanjut'] ?? 'index.php';
$tujuan = in_array($tujuan, ['checkout.php', 'index.php'], true) ? $tujuan : 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val_email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($val_email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $koneksi->prepare("SELECT id_pembeli, nama_pembeli, password FROM pembeli WHERE email = ?");
        $stmt->execute([$val_email]);
        $pembeli = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pembeli && password_verify($password, $pembeli['password'])) {
            $_SESSION['pembeli_id'] = $pembeli['id_pembeli'];
            $_SESSION['pembeli_nama'] = $pembeli['nama_pembeli'];
            header('Location: ' . $tujuan);
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:48px;">
  <div class="wrap" style="max-width:440px;">
    <h2 class="section-title">Login</h2>
    <p class="section-sub">Masuk untuk checkout lebih cepat tanpa isi ulang data pengiriman.</p>

    <?php if ($error): ?>
      <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php?lanjut=<?= urlencode($tujuan) ?>" class="form-card">
      <div class="form-field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autofocus value="<?= htmlspecialchars($val_email) ?>">
      </div>
      <div class="form-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-gold" style="width:100%;">Masuk</button>
    </form>

    <p style="text-align:center; margin-top:20px;">
      Belum punya akun?
      <a href="daftar.php?lanjut=<?= urlencode($tujuan) ?>" style="color:var(--soga);">Daftar di sini</a>
    </p>
    <p style="text-align:center; margin-top:8px;">
      <a href="checkout.php" style="color:var(--soga); font-size:14px;">Lanjut checkout tanpa akun →</a>
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
