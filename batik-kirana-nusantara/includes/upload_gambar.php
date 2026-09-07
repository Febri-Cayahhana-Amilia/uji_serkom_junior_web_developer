<?php
/**
 * Helper untuk upload foto produk asli (bukan placeholder).
 * Mengembalikan nama file baru jika berhasil, atau null jika tidak ada
 * file yang diupload / terjadi error.
 */

function proses_upload_gambar(string $field_name): ?array {
    if (empty($_FILES[$field_name]['name']) || $_FILES[$field_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // tidak ada file diupload, biarkan foto lama/kosong
    }

    if ($_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload gagal (kode error: ' . $_FILES[$field_name]['error'] . ').'];
    }

    $ekstensi_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];
    $ukuran_maks = 2 * 1024 * 1024; // 2MB — sesuai batas default banyak instalasi PHP

    $namaAsli = $_FILES[$field_name]['name'];
    $ekstensi = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));

    if (!in_array($ekstensi, $ekstensi_diizinkan, true)) {
        return ['error' => 'Format foto harus JPG, PNG, atau WEBP.'];
    }

    if ($_FILES[$field_name]['size'] > $ukuran_maks) {
        return ['error' => 'Ukuran foto maksimal 3MB.'];
    }

    // Pastikan file yang diupload benar-benar gambar (bukan file lain yang disamarkan)
    $infoGambar = @getimagesize($_FILES[$field_name]['tmp_name']);
    if ($infoGambar === false) {
        return ['error' => 'File yang diupload bukan gambar yang valid.'];
    }

    $namaBaru = uniqid('produk_', true) . '.' . $ekstensi;
    $tujuan = __DIR__ . '/../uploads/produk/' . $namaBaru;

    if (!move_uploaded_file($_FILES[$field_name]['tmp_name'], $tujuan)) {
        return ['error' => 'Gagal menyimpan foto ke server.'];
    }

    return ['nama_file' => $namaBaru];
}
