<?php
/**
 * Helper Keranjang Belanja — disimpan di session, per pengunjung.
 * Struktur: $_SESSION['keranjang'] = [ kunci => jumlah, ... ]
 * $kunci berbentuk "id_produk" (tanpa varian) atau "id_produk_UKURAN"
 * (contoh: "12_L") — sehingga satu produk yang sama bisa ada beberapa
 * baris di keranjang untuk ukuran yang berbeda, tanpa perlu kolom baru
 * di database.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

/** Bentuk kunci baris keranjang dari id_produk + ukuran (opsional). */
function keranjang_kunci(int $id_produk, string $ukuran = ''): string
{
    $ukuran = trim($ukuran);
    return $ukuran !== '' ? $id_produk . '_' . strtoupper($ukuran) : (string)$id_produk;
}

/** Tambah produk ke keranjang (atau tambah jumlahnya jika sudah ada). */
function keranjang_tambah(int $id_produk, int $jumlah = 1, string $ukuran = ''): void
{
    if ($jumlah < 1) $jumlah = 1;
    $kunci = keranjang_kunci($id_produk, $ukuran);
    if (!isset($_SESSION['keranjang'][$kunci])) {
        $_SESSION['keranjang'][$kunci] = 0;
    }
    $_SESSION['keranjang'][$kunci] += $jumlah;
}

/** Ubah jumlah baris tertentu di keranjang (berdasarkan kunci). Jumlah 0 = hapus. */
function keranjang_ubah_jumlah(string $kunci, int $jumlah): void
{
    if ($jumlah <= 0) {
        unset($_SESSION['keranjang'][$kunci]);
    } else {
        $_SESSION['keranjang'][$kunci] = $jumlah;
    }
}

/** Hapus satu baris dari keranjang (berdasarkan kunci). */
function keranjang_hapus(string $kunci): void
{
    unset($_SESSION['keranjang'][$kunci]);
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

    // Ambil daftar id_produk unik dari semua kunci (format "id" atau "id_UKURAN")
    $idList = [];
    foreach (array_keys($isi) as $kunci) {
        $idList[] = (int)explode('_', (string)$kunci, 2)[0];
    }
    $idList = array_values(array_unique($idList));

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

    foreach ($isi as $kunci => $jumlah) {
        $bagian = explode('_', (string)$kunci, 2);
        $id_produk = (int)$bagian[0];
        $ukuran = $bagian[1] ?? '';

        if (!isset($produkById[$id_produk])) {
            // Produk sudah dihapus dari database — buang dari keranjang
            unset($_SESSION['keranjang'][$kunci]);
            continue;
        }
        $p = $produkById[$id_produk];
        $jumlah = min((int)$jumlah, (int)$p['stok'] > 0 ? (int)$p['stok'] : (int)$jumlah);
        $subtotal = $jumlah * (float)$p['harga'];
        $total += $subtotal;

        $namaTampil = $p['nama_produk'];
        if ($ukuran !== '') {
            $namaTampil .= ' (Ukuran: ' . $ukuran . ')';
        }

        $items[] = [
            'kunci'       => (string)$kunci,
            'id_produk'   => $id_produk,
            'ukuran'      => $ukuran,
            'nama_produk' => $namaTampil,
            'harga'       => (float)$p['harga'],
            'stok'        => (int)$p['stok'],
            'gambar'      => $p['gambar'],
            'jumlah'      => $jumlah,
            'subtotal'    => $subtotal,
        ];
    }

    return ['items' => $items, 'total' => $total];
}
