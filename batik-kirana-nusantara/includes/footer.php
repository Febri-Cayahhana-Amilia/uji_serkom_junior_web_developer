<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-col footer-brand-col">
      <p class="footer-brand">Batik Kirana <span>Nusantara</span></p>
      <p class="footer-note">Warisan tradisi dalam setiap guratan. Batik tulis & cap pilihan karya perajin lokal untuk pesona Nusantara Anda.</p>
    </div>
    
    <div class="footer-col">
      <h4>Kategori</h4>
      <a href="<?= $base_url ?? '' ?>produk.php">Semua Produk</a>
      <a href="<?= $base_url ?? '' ?>produk.php?kategori=1">Batik Tulis</a>
      <a href="<?= $base_url ?? '' ?>produk.php?kategori=2">Batik Cap</a>
      <a href="<?= $base_url ?? '' ?>produk.php?kategori=3">Aksesoris</a>
    </div>
    
    <div class="footer-col">
      <h4>Perusahaan</h4>
      <a href="<?= $base_url ?? '' ?>tentang.php">Tentang Kami</a>
      <a href="<?= $base_url ?? '' ?>kontak.php">Kontak</a>
    </div>
    
    <div class="footer-col">
      <h4>Kontak</h4>
      <div style="display: flex; gap: 12px; margin-top: 10px; align-items: center; flex-wrap: wrap;">
        <!-- WhatsApp Icon (Pesan Salam Umum) -->
        <a href="https://wa.me/6281334568278?text=<?= urlencode('Halo Batik Kirana, saya ingin bertanya mengenai produk dan layanan Anda.') ?>" target="_blank" title="WhatsApp: 0813-3456-8278" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; border: 1.5px solid var(--gold); color: var(--gold); text-decoration: none;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
          </svg>
        </a>

        <!-- Instagram Icon -->
        <a href="https://instagram.com/febri.cayahhana" target="_blank" title="Instagram: @febri.cayahhana" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; border: 1.5px solid var(--gold); color: var(--gold); text-decoration: none;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
          </svg>
        </a>

        <!-- Email Icon -->
        <a href="mailto:febri.cayahhana28@gmail.com" title="Email: febri.cayahhana28@gmail.com" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; border: 1.5px solid var(--gold); color: var(--gold); text-decoration: none;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
          </svg>
        </a>

        <!-- Lokasi Icon -->
        <a href="https://maps.google.com/?q=Dolopo,+Madiun,+Jawa+Timur" target="_blank" title="Lokasi: Dolopo, Madiun, Jawa Timur" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; border: 1.5px solid var(--gold); color: var(--gold); text-decoration: none;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
        </a>
      </div>
    </div>
  </div>

  <div class="wrap">
    <p class="footer-copy">© <?= date('Y') ?> Batik Kirana Nusantara — Proyek Uji Kompetensi Junior Web Developer</p>
  </div>
</footer>

<script src="<?= $base_url ?? '' ?>js/script.js"></script>
</body>
</html>