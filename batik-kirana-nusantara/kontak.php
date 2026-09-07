<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/kirim_email.php';
$base_url = '';
$judul_halaman = 'Kontak';

$pesan_sukses = '';
$pesan_error = '';

// Tangkap pesan default dari URL (misal dari halaman detail produk)
$pesan_default = isset($_GET['pesan']) ? trim($_GET['pesan']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $isi_pesan = trim($_POST['pesan'] ?? '');

    if ($nama === '' || $email === '' || $isi_pesan === '') {
        $pesan_error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan_error = 'Format email tidak valid.';
    } else {
        $stmt = $koneksi->prepare("INSERT INTO pesan_kontak (nama, email, pesan) VALUES (?, ?, ?)");
        if ($stmt->execute([$nama, $email, $isi_pesan])) {
            $hasilEmail = kirim_email_kontak($nama, $email, $isi_pesan);
            if ($hasilEmail['berhasil']) {
                $pesan_sukses = 'Terima kasih! Pesan kamu sudah tersimpan dan berhasil terkirim via Email.';
            } else {
                $pesan_error = 'Pesan tersimpan, tetapi notifikasi email gagal terkirim.';
            }
        } else {
            $pesan_error = 'Gagal mengirim pesan, silakan coba lagi.';
        }
    }
}

$val_nama = $_POST['nama'] ?? '';
$val_email = $_POST['email'] ?? '';
$val_pesan = $_POST['pesan'] ?? $pesan_default;

require __DIR__ . '/includes/header.php';
?>

<header class="kontak-hero">
  <div class="hero-pattern" aria-hidden="true"></div>
  <div class="wrap">
    <p class="hero-kicker">Kami Senang Mendengar Dari Kamu</p>
    <h1>Hubungi Kami</h1>
    <p class="kontak-hero-lede">Pilih akses komunikasi yang paling nyaman untuk terhubung dengan Batik Kirana.</p>
  </div>
</header>

<section class="section" style="padding-top:56px;">
  <div class="wrap">
    <div class="kontak-layout">

      <!-- Kartu Pilihan Akses Kontak -->
      <div class="kontak-info-card">
        <h3>Info Kontak</h3>
        <p class="kontak-info-lede">Silakan pilih jalur komunikasi langsung via WhatsApp, Email, atau isi formulir di samping.</p>

        <!-- Pilihan 1: WhatsApp -->
        <div class="info-row">
          <span class="info-icon" style="display:flex; align-items:center; justify-content:center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
            </svg>
          </span>
          <div>
            <span class="info-label">WHATSAPP (RESPON CEPAT)</span>
            <a href="https://wa.me/6281334568278?text=<?= urlencode('Halo Batik Kirana, saya ingin bertanya mengenai produk Anda.') ?>" target="_blank" style="font-weight:600;">
              Chat via WhatsApp (0813-3456-8278) &rarr;
            </a>
          </div>
        </div>

        <!-- Pilihan 2: Email -->
        <div class="info-row">
          <span class="info-icon" style="display:flex; align-items:center; justify-content:center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
              <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
          </span>
          <div>
            <span class="info-label">EMAIL RESMI</span>
            <a href="mailto:febri.cayahhana28@gmail.com">febri.cayahhana28@gmail.com</a>
          </div>
        </div>

        <!-- Lokasi -->
        <div class="info-row">
          <span class="info-icon" style="display:flex; align-items:center; justify-content:center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
          </span>
          <div>
            <span class="info-label">LOKASI</span>
            <span>Dolopo, Madiun, Jawa Timur</span>
          </div>
        </div>

        <div class="kontak-motif-mini" aria-hidden="true">
          <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
            <circle cx="60" cy="60" r="34" fill="none" stroke="var(--gold)" stroke-width="1.2"/>
            <circle cx="60" cy="60" r="22" fill="none" stroke="var(--gold)" stroke-width="1.2"/>
            <circle cx="60" cy="60" r="4" fill="var(--gold)"/>
          </svg>
        </div>
      </div>

      <!-- Form Kirim Pesan Email -->
      <div class="form-card kontak-form-card">
        <?php if ($pesan_sukses || $pesan_error): ?>
          <div class="toast-container" role="status" aria-live="polite">
            <?php if ($pesan_sukses): ?>
              <div class="toast toast-success">
                <span class="toast-icon">&#10003;</span>
                <span class="toast-text"><?= htmlspecialchars($pesan_sukses) ?></span>
                <button type="button" class="toast-close" aria-label="Tutup">&times;</button>
              </div>
            <?php endif; ?>
            <?php if ($pesan_error): ?>
              <div class="toast toast-error">
                <span class="toast-icon">&#33;</span>
                <span class="toast-text"><?= htmlspecialchars($pesan_error) ?></span>
                <button type="button" class="toast-close" aria-label="Tutup">&times;</button>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="kontak.php">
          <div class="form-field">
            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" placeholder="Nama lengkap kamu" value="<?= htmlspecialchars($val_nama) ?>" required>
          </div>
          <div class="form-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="email@contoh.com" value="<?= htmlspecialchars($val_email) ?>" required>
          </div>
          <div class="form-field">
            <label for="pesan">Pesan</label>
            <textarea id="pesan" name="pesan" placeholder="Tulis pertanyaan atau produk yang kamu mau…" required><?= htmlspecialchars($val_pesan) ?></textarea>
          </div>
          <button type="submit" class="btn btn-gold" style="width:100%;">Kirim Pesan via Email</button>
        </form>
      </div>

    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>