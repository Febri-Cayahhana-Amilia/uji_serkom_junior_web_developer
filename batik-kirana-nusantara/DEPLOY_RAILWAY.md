# Panduan Deploy ke Railway (khusus project ini)

Railway menjalankan PHP lewat Nixpacks + **nginx** (bukan Apache), jadi
file `.htaccess` di `includes/`, `database/`, `vendor/`, `uploads/`
**tidak berfungsi** di Railway — nginx tidak membacanya sama sekali.
Ikuti langkah-langkah di bawah ini sebagai gantinya.

## 1. Foto produk akan HILANG setiap redeploy kalau tidak dipasang Volume

Filesystem Railway bersifat *ephemeral* — semua file yang ditulis saat
aplikasi jalan (termasuk foto produk yang diupload admin ke
`uploads/produk/`) akan hilang setiap kali ada deploy ulang atau
restart, kecuali kamu pasang **Volume**.

Cara pasang:
1. Buka service PHP kamu di Railway → tab **Volumes** → tambah volume.
2. Set **Mount Path** ke `/app/uploads/produk` (Railway meletakkan kode
   di `/app` di dalam container).
3. Redeploy. Setelah itu file yang diupload lewat panel admin akan
   tetap ada walau ada deploy baru.

Tanpa ini, setiap kali kamu push perubahan kode, semua foto produk
yang sempat diupload lewat `admin/tambah.php` / `admin/edit.php` akan
hilang dan kolom `gambar` di database jadi menunjuk ke file yang
sudah tidak ada.

## 2. `database/batik_kirana.sql` bisa diunduh publik

Karena project ini tidak punya folder `public/` (bukan struktur
Laravel), Nixpacks akan menjadikan **root project sebagai document
root** — artinya nginx akan menyajikan apa pun yang ada di project
sebagai file statis kalau bukan `.php`, termasuk file `.sql`.
`.htaccess` tidak bisa mencegah ini di Railway.

Langkah amannya:
1. **Sebelum** deploy final, jalankan isi `database/batik_kirana.sql`
   sekali lewat tab **Data / Query** di dashboard service Postgres
   Railway kamu (copy-paste seluruh isi file lalu jalankan), ATAU
   lewat `psql` dari komputer memakai connection string dari Railway.
2. Buat akun admin lewat `database/setup_admin.php` — paling aman
   dijalankan lewat **Railway CLI**, bukan lewat URL publik:
   ```
   railway run php database/setup_admin.php
   ```
   (perintah ini otomatis punya akses ke environment variable database
   yang sama dengan service kamu, tanpa perlu lewat browser sama
   sekali).
3. **Setelah schema & akun admin dibuat**, hapus dua file ini dari
   project lalu redeploy:
   - `database/batik_kirana.sql`
   - `database/setup_admin.php`

   Keduanya cuma dipakai sekali di awal — tidak perlu ikut ter-deploy
   selamanya.

## 3. Kredensial database — pakai Variables Railway, jangan hardcode

`includes/db.php` sudah dibuat membaca environment variable duluan
(`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`) sebelum jatuh
ke nilai hardcode. Di Railway:

1. Buka service PHP kamu → tab **Variables**.
2. Kalau Postgres-nya juga berupa service Postgres di project Railway
   yang sama, isi begini (memakai referensi ke service Postgres, tidak
   perlu copy-paste manual):
   ```
   DB_HOST=${{Postgres.PGHOST}}
   DB_PORT=${{Postgres.PGPORT}}
   DB_NAME=${{Postgres.PGDATABASE}}
   DB_USER=${{Postgres.PGUSER}}
   DB_PASS=${{Postgres.PGPASSWORD}}
   ```
   Ini memakai jaringan privat Railway (lebih cepat, tidak kena biaya
   Network Egress, dan databasenya tidak perlu diekspos ke internet
   lewat TCP Proxy publik seperti `altaria.proxy.rlwy.net` yang
   sekarang dipakai).
3. Kalau Postgres-nya di luar Railway (atau kamu memang perlu akses
   dari luar project), baru pakai host/port dari TCP Proxy publik.

⚠️ **Ganti password database Railway kamu dari dashboard Railway**
(Postgres service → Variables → regenerate/rotate), karena password
lama sempat tertulis polos di `includes/db.php` sebelum diperbaiki.

## 4. Ringkasan checklist sebelum submit/hosting final

- [ ] Volume terpasang di `/app/uploads/produk`
- [ ] Schema `.sql` sudah dijalankan ke Postgres Railway
- [ ] **Migrasi `database/tambah_pembayaran_kembalian.sql` sudah dijalankan** (kolom `pembayaran` & `kembalian` di tabel `pesanan`, dipakai fitur cetak struk transaksi manual)
- [ ] Akun admin dibuat lewat `railway run`, bukan lewat URL publik
- [ ] `database/batik_kirana.sql` & `database/setup_admin.php` dihapus dari project
- [ ] `DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS` diisi di tab Variables Railway
- [ ] Password database Railway sudah di-rotate
- [ ] Password admin default (`admin123`) sudah diganti setelah login pertama
