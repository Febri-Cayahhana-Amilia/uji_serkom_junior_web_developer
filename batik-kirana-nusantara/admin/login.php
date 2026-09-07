<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/../includes/db.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $koneksi->prepare("SELECT id_admin, username, password FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id_admin'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin — Batik Kirana Nusantara</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-shell">

<div class="wrap" style="max-width:440px; padding-top:96px;">
  <h2 class="section-title" style="text-align:center;">Login Admin</h2>
  <p class="section-sub" style="text-align:center; margin-left:auto; margin-right:auto;">Batik Kirana Nusantara</p>

  <div class="form-card" style="max-width:none;">
    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>
      </div>
      <div class="form-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-gold" style="width:100%;">Masuk</button>
    </form>
  </div>

  <p style="text-align:center; margin-top:20px;"><a href="../index.php" style="color:var(--soga);">← Kembali ke website</a></p>
</div>

</body>
</html>
