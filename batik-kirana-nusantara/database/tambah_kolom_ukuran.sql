-- Migrasi: tambah kolom ukuran_tersedia pada tabel produk
-- Jalankan SEKALI di database PostgreSQL yang dipakai Railway.
--
-- Kolom ini menyimpan daftar ukuran yang tersedia untuk sebuah produk,
-- dipisahkan koma, contoh: "S,M,L,XL". Kosong / NULL berarti produk
-- tidak butuh pilihan ukuran (misalnya kain per meter atau aksesoris).

ALTER TABLE produk ADD COLUMN IF NOT EXISTS ukuran_tersedia VARCHAR(100) DEFAULT NULL;
