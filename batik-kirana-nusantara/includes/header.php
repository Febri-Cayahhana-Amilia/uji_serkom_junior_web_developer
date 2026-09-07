<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$halaman_ini = basename($_SERVER['SCRIPT_NAME']);
$is_lokal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost:8000', 'localhost', '127.0.0.1:8000', '127.0.0.1']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($judul_halaman) ? htmlspecialchars($judul_halaman) . ' — ' : '' ?>Batik Kirana Nusantara</title>
<meta name="description" content="Batik tulis dan batik cap karya perajin lokal — Batik Kirana Nusantara.">
<link rel="stylesheet" href="<?= $base_url ?? '' ?>css/style.css">
</head>
<body>

<nav class="nav">
  <div class="wrap nav-inner">
    <a href="<?= $base_url ?? '' ?>index.php" class="brand">
      <span class="brand-mark" aria-hidden="true"></span>
      Batik Kirana <span>Nusantara</span>
    </a>
    <ul class="nav-links">
      <li><a href="<?= $base_url ?? '' ?>index.php" class="<?= $halaman_ini === 'index.php' ? 'aktif' : '' ?>">Beranda</a></li>
      <li><a href="<?= $base_url ?? '' ?>produk.php" class="<?= in_array($halaman_ini, ['produk.php', 'produk_detail.php']) ? 'aktif' : '' ?>">Produk</a></li>
      <li><a href="<?= $base_url ?? '' ?>tentang.php" class="<?= $halaman_ini === 'tentang.php' ? 'aktif' : '' ?>">Tentang</a></li>
      <li><a href="<?= $base_url ?? '' ?>kontak.php" class="<?= $halaman_ini === 'kontak.php' ? 'aktif' : '' ?>">Kontak</a></li>
      <li><a href="https://febri-portofolio.netlify.app/" class="nav-back">↩ Web Profil</a></li>
      <?php if ($is_lokal): ?>
      <li>
        <a href="<?= $base_url ?? '' ?>admin/login.php" class="nav-admin" aria-label="Login Admin" title="Login Admin">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>