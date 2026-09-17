<?php
/**
 * Helper Keranjang Belanja — disimpan di session, per pengunjung.
 * Struktur: $_SESSION['keranjang'] = [ id_produk => jumlah, ... ]
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

/** Tambah produk ke keranjang (atau tambah jumlahnya jika sudah ada). */
function keranjang_tambah(int $id_produk, int $jumlah = 1): void
{
    if ($jumlah < 1) $jumlah = 1;
    if (!isset($_SESSION['keranjang'][$id_produk])) {
        $_SESSION['keranjang'][$id_produk] = 0;
    }
    $_SESSION['keranjang'][$id_produk] += $jumlah;
}

/** Ubah jumlah item tertentu di keranjang. Jumlah 0 = hapus. */
function keranjang_ubah_jumlah(int $id_produk, int $jumlah): void
{
    if ($jumlah <= 0) {
        unset($_SESSION['keranjang'][$id_produk]);
    } else {
        $_SESSION['keranjang'][$id_produk] = $jumlah;
    }
}

/** Hapus satu item dari keranjang. */
function keranjang_hapus(int $id_produk): void
{
    unset($_SESSION['keranjang'][$id_produk]);
}

/** Kosongkan seluruh keranjang (dipanggil setelah checkout berhasil). */
function keranjang_kosongkan(): void
{
    $_SESSION['keranjang'] = [];
}

/** Jumlah total baris/jenis produk berbeda di keranjang (untuk badge di navbar). */
function keranjang_jumlah_jenis(): int
{
    return count($_SESSION['keranjang'] ?? []);
}

/** Jumlah total unit barang (menjumlahkan qty semua item) — untuk badge di navbar. */
function keranjang_total_unit(): int
{
    return array_sum($_SESSION['keranjang'] ?? []);
}

/**
 * Ambil isi keranjang lengkap dengan data produk terbaru dari database
 * (nama, harga, stok, gambar), plus subtotal per item dan total keseluruhan.
 * Produk yang sudah tidak ada di database otomatis dibuang dari keranjang.
 */
function keranjang_isi(PDO $koneksi): array
{
    $isi = $_SESSION['keranjang'] ?? [];
    if (empty($isi)) {
        return ['items' => [], 'total' => 0];
    }

    $idList = array_keys($isi);
    $placeholder = implode(',', array_fill(0, count($idList), '?'));
    $stmt = $koneksi->prepare(
        "SELECT id_produk, nama_produk, harga, stok, gambar FROM produk WHERE id_produk IN ($placeholder)"
    );
    $stmt->execute($idList);
    $produkList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $produkById = [];
    foreach ($produkList as $p) {
        $produkById[(int)$p['id_produk']] = $p;
    }

    $items = [];
    $total = 0;

    foreach ($isi as $id_produk => $jumlah) {
        $id_produk = (int)$id_produk;
        if (!isset($produkById[$id_produk])) {
            // Produk sudah dihapus dari database — buang dari keranjang
            unset($_SESSION['keranjang'][$id_produk]);
            continue;
        }
        $p = $produkById[$id_produk];
        $jumlah = min((int)$jumlah, (int)$p['stok'] > 0 ? (int)$p['stok'] : (int)$jumlah);
        $subtotal = $jumlah * (float)$p['harga'];
        $total += $subtotal;

        $items[] = [
            'id_produk'   => $id_produk,
            'nama_produk' => $p['nama_produk'],
            'harga'       => (float)$p['harga'],
            'stok'        => (int)$p['stok'],
            'gambar'      => $p['gambar'],
            'jumlah'      => $jumlah,
            'subtotal'    => $subtotal,
        ];
    }

    return ['items' => $items, 'total' => $total];
}
