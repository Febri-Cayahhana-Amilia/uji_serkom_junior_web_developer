# Tugas Uji Serkom — Skema Junior Web Developer

Paket ini berisi dua website sesuai soal:

```
portfolio-febri/            → Website statis (profil pribadi)
batik-kirana-nusantara/     → Website dinamis (toko batik, PHP + PostgreSQL)
```

Web profil (`portfolio-febri/index.html`) sudah berisi tautan ke web usaha
(`batik-kirana-nusantara/index.php`), dan sebaliknya web usaha punya tautan
"↩ Web Profil" di navigasinya — sesuai poin 3 pada soal.

## 1. Menjalankan web profil (statis)

Tidak perlu server. Buka `portfolio-febri/index.html` langsung di browser,
atau klik kanan di VS Code → "Open with Live Server" kalau extension itu
terpasang.

## 2. Menjalankan web usaha (dinamis — Batik Kirana Nusantara)

Kamu pakai pgAdmin + VS Code, jadi setup-nya seperti ini (tidak perlu
XAMPP/Laragon):

### a. Siapkan database di pgAdmin

1. Buka pgAdmin, klik kanan **Databases** → **Create** → **Database**,
   beri nama `batik_kirana`.
2. Klik kanan database `batik_kirana` yang baru dibuat → **Query Tool**.
3. Buka file `batik-kirana-nusantara/database/batik_kirana.sql`, salin
   semua isinya, tempel ke Query Tool, lalu jalankan (tombol ▶ / F5).
   Ini akan membuat tabel `kategori`, `produk`, `admin`, `pesan_kontak`
   beserta 6 data produk contoh.

### b. Sambungkan PHP ke PostgreSQL

1. Buka `batik-kirana-nusantara/includes/db.php` di VS Code, sesuaikan
   `$db_user` dan `$db_pass` dengan akun PostgreSQL kamu di pgAdmin
   (defaultnya sering `postgres` / password yang kamu buat saat instal).
2. Pastikan ekstensi **pdo_pgsql** aktif di PHP kamu. Cara cek: jalankan
   `php -m` di terminal VS Code, lihat apakah `pdo_pgsql` muncul di
   daftar. Kalau belum aktif, buka `php.ini` (cari lokasinya dengan
   `php --ini`), hapus tanda `;` di depan baris
   `;extension=pdo_pgsql`, simpan, lalu restart terminal.

### c. Jalankan server PHP dari VS Code

1. Buka folder `batik-kirana-nusantara` di VS Code.
2. Buka terminal (Terminal → New Terminal), jalankan:
   ```
   php -S localhost:8000
   ```
3. Buka `http://localhost:8000/database/setup_admin.php` di browser —
   sekali saja — untuk membuat akun admin:
   - Username: `admin`
   - Password: `admin123`
   (Password di-hash otomatis oleh PHP saat skrip ini jalan.)
4. Setelah itu **hapus** file `setup_admin.php` (sudah tidak diperlukan).
5. Buka `http://localhost:8000/` untuk melihat website, atau
   `http://localhost:8000/admin/login.php` untuk masuk ke panel kelola
   produk (tambah/edit/hapus produk).

Supaya tautan "↩ Web Profil" di web usaha ikut berfungsi, taruh folder
`portfolio-febri` sejajar dengan `batik-kirana-nusantara` (satu folder
induk yang sama), lalu jalankan `php -S localhost:8000` dari folder
induk itu — bukan dari dalam `batik-kirana-nusantara` langsung.

## Struktur fitur web usaha

- `index.php` — beranda + produk terbaru
- `produk.php` — katalog produk, bisa difilter per kategori
- `produk_detail.php` — detail satu produk
- `tentang.php` — profil usaha
- `kontak.php` — form kontak, pesan disimpan ke tabel `pesan_kontak`
- `admin/` — login, dashboard, tambah/edit/hapus produk (CRUD ke PostgreSQL)

## Catatan untuk pengumpulan

Soal meminta file `.rar`. Karena lingkungan ini tidak punya alat pembuat
`.rar`, folder ini dikemas sebagai `.zip`. Kamu bisa langsung unggah
`.zip`-nya, atau kompres ulang ke `.rar` pakai WinRAR/7-Zip di komputer
kamu kalau formulir pengumpulan mewajibkan `.rar`.

Tenggat: **Senin, 07 September 2026**.
