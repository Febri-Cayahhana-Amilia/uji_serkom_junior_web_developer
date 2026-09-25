-- Migrasi: tambah kolom pembayaran & kembalian pada tabel pesanan
-- Jalankan SEKALI di database PostgreSQL yang dipakai Railway.
--
-- Kolom ini dipakai oleh fitur "Transaksi Manual" (admin/transaksi_baru.php)
-- untuk mencatat berapa uang yang dibayarkan kasir/pelanggan dan berapa
-- kembaliannya, lalu ditampilkan di struk (admin/struk.php).
-- Untuk pesanan yang dibuat lewat checkout online (checkout.php), kolom ini
-- boleh tetap NULL karena pembayarannya tidak dicatat manual oleh admin.

ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS uang_diterima NUMERIC(12,2) DEFAULT NULL;
ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS kembalian NUMERIC(12,2) DEFAULT NULL;
