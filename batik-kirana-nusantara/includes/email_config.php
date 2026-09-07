<?php
/**
 * Konfigurasi pengiriman email untuk form kontak.
 *
 * PENTING: Jangan pakai password Gmail biasa di sini — Gmail akan
 * menolaknya. Kamu harus membuat "App Password" khusus:
 *
 * 1. Aktifkan Verifikasi 2 Langkah di akun Gmail kamu (wajib):
 *    https://myaccount.google.com/security
 * 2. Buka https://myaccount.google.com/apppasswords
 * 3. Buat App Password baru (pilih app "Mail", perangkat bebas),
 *    lalu copy 16 digit kode yang muncul (tanpa spasi).
 * 4. Isi dua baris di bawah ini.
 */

define('SMTP_USERNAME', 'febri.cayahhana28@gmail.com'); // akun Gmail pengirim
define('SMTP_APP_PASSWORD', 'mejwosrsdwfpbncu');       // App Password, BUKAN password biasa
define('EMAIL_TUJUAN', 'febri.cayahhana28@gmail.com');      // email yang menerima pesan form kontak
