-- Migrasi (opsional): kolom uang diterima & kembalian untuk transaksi tunai.
-- Aplikasi sudah menambahkannya otomatis; file ini hanya untuk jaga-jaga
-- kalau user database tidak diizinkan menjalankan ALTER dari aplikasi.

ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS uang_diterima NUMERIC(14,2) DEFAULT NULL;
ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS kembalian     NUMERIC(14,2) DEFAULT NULL;
