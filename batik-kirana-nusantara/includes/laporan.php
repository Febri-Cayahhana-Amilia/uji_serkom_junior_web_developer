<?php


const LAPORAN_STATUS = ['Menunggu Pembayaran', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'];

function laporan_tanggal_valid(string $t): bool
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $t, $m)) return false;
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

/** Baca & bersihkan parameter filter dari $_GET. */
function laporan_filter(array $q): array
{
    $hariIni = date('Y-m-d');
    $periode = (string)($q['periode'] ?? '');

    switch ($periode) {
        case 'hari_ini':
            $dari = $sampai = $hariIni;
            break;
        case '7_hari':
            $dari = date('Y-m-d', strtotime('-6 days'));
            $sampai = $hariIni;
            break;
        case 'bulan_ini':
            $dari = date('Y-m-01');
            $sampai = $hariIni;
            break;
        case 'bulan_lalu':
            $dari = date('Y-m-01', strtotime('first day of last month'));
            $sampai = date('Y-m-t', strtotime('first day of last month'));
            break;
        case 'tahun_ini':
            $dari = date('Y-01-01');
            $sampai = $hariIni;
            break;
        default:
            $periode = '';
            $dari = (string)($q['dari'] ?? date('Y-m-01'));
            $sampai = (string)($q['sampai'] ?? $hariIni);
    }

    if (!laporan_tanggal_valid($dari)) $dari = date('Y-m-01');
    if (!laporan_tanggal_valid($sampai)) $sampai = $hariIni;
    if ($dari > $sampai) [$dari, $sampai] = [$sampai, $dari];

    $status = (string)($q['status'] ?? '');
    if (!in_array($status, LAPORAN_STATUS, true)) $status = '';

    return [
        'periode'     => $periode,
        'dari'        => $dari,
        'sampai'      => $sampai,
        'id_kategori' => max(0, (int)($q['kategori'] ?? 0)),
        'status'      => $status,
    ];
}

/** Query string filter (untuk link ekspor / cetak). */
function laporan_query(array $f): string
{
    return http_build_query([
        'dari'     => $f['dari'],
        'sampai'   => $f['sampai'],
        'kategori' => $f['id_kategori'] ?: '',
        'status'   => $f['status'],
    ]);
}

/**
 * Ambil seluruh data laporan untuk filter tertentu.
 * @return array{pesanan:array, ringkasan:array, per_kategori:array, per_status:array, terlaris:array, harian:array}
 */
function laporan_ambil(PDO $k, array $f, int $batasTerlaris = 5): array
{
    // ---- Kondisi dasar pada tabel pesanan p ----
    $where = "p.dibuat_pada::date BETWEEN ? AND ?";
    $params = [$f['dari'], $f['sampai']];
    if ($f['status'] !== '') {
        $where .= " AND p.status = ?";
        $params[] = $f['status'];
    }

    // ---- Daftar transaksi (dengan filter kategori bila dipilih) ----
    $wherePesanan = $where;
    $paramsPesanan = $params;
    if ($f['id_kategori'] > 0) {
        $wherePesanan .= " AND EXISTS (
            SELECT 1 FROM detail_pesanan d2
            JOIN produk pr2 ON pr2.id_produk = d2.id_produk
            WHERE d2.id_pesanan = p.id_pesanan AND pr2.id_kategori = ?)";
        $paramsPesanan[] = $f['id_kategori'];
    }
    $stmt = $k->prepare(
        "SELECT p.*,
                (SELECT string_agg(d.nama_produk || ' ×' || d.jumlah, ', ' ORDER BY d.id_detail)
                   FROM detail_pesanan d WHERE d.id_pesanan = p.id_pesanan) AS ringkasan_item
         FROM pesanan p
         WHERE $wherePesanan
         ORDER BY p.dibuat_pada DESC"
    );
    try {
        $stmt->execute($paramsPesanan);
    } catch (PDOException $e) {
        // Cadangan kalau kolom id_detail tidak ada di tabel detail_pesanan
        $stmt = $k->prepare(
            "SELECT p.*,
                    (SELECT string_agg(d.nama_produk || ' ×' || d.jumlah, ', ')
                       FROM detail_pesanan d WHERE d.id_pesanan = p.id_pesanan) AS ringkasan_item
             FROM pesanan p
             WHERE $wherePesanan
             ORDER BY p.dibuat_pada DESC"
        );
        $stmt->execute($paramsPesanan);
    }
    $pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---- Kondisi untuk agregasi item (tidak termasuk yang dibatalkan) ----
    $whereItem = $where . " AND p.status <> 'Dibatalkan'";
    $paramsItem = $params;
    if ($f['id_kategori'] > 0) {
        $whereItem .= " AND pr.id_kategori = ?";
        $paramsItem[] = $f['id_kategori'];
    }
    $dasarItem = "FROM detail_pesanan d
                  JOIN pesanan p ON p.id_pesanan = d.id_pesanan
                  LEFT JOIN produk pr ON pr.id_produk = d.id_produk
                  LEFT JOIN kategori kt ON kt.id_kategori = pr.id_kategori
                  WHERE $whereItem";

    // ---- Rekap total ----
    $stmt = $k->prepare(
        "SELECT COUNT(DISTINCT p.id_pesanan) AS transaksi,
                COALESCE(SUM(d.subtotal), 0) AS omzet,
                COALESCE(SUM(d.jumlah), 0) AS unit
         $dasarItem"
    );
    $stmt->execute($paramsItem);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    $transaksi = (int)$r['transaksi'];
    $omzet = (float)$r['omzet'];
    $ringkasan = [
        'transaksi'  => $transaksi,
        'omzet'      => $omzet,
        'unit'       => (int)$r['unit'],
        'rata_rata'  => $transaksi > 0 ? $omzet / $transaksi : 0,
        'dibatalkan' => count(array_filter($pesanan, fn($x) => $x['status'] === 'Dibatalkan')),
        'semua'      => count($pesanan),
    ];

    // ---- Per kategori ----
    $stmt = $k->prepare(
        "SELECT COALESCE(kt.nama_kategori, 'Tanpa Kategori') AS kategori,
                SUM(d.jumlah) AS unit, SUM(d.subtotal) AS omzet
         $dasarItem
         GROUP BY 1 ORDER BY omzet DESC"
    );
    $stmt->execute($paramsItem);
    $perKategori = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---- Produk terlaris ----
    $limit = $batasTerlaris > 0 ? ' LIMIT ' . (int)$batasTerlaris : '';
    $stmt = $k->prepare(
        "SELECT d.nama_produk, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omzet
         $dasarItem
         GROUP BY d.nama_produk ORDER BY terjual DESC, omzet DESC$limit"
    );
    $stmt->execute($paramsItem);
    $terlaris = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---- Per status (semua status, mengikuti filter tanggal & kategori) ----
    $stmt = $k->prepare(
        "SELECT p.status, COUNT(*) AS jumlah, COALESCE(SUM(p.total_harga), 0) AS nilai
         FROM pesanan p WHERE $wherePesanan GROUP BY p.status"
    );
    $stmt->execute($paramsPesanan);
    $perStatus = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $perStatus[$row['status']] = $row;
    }

    // ---- Omzet harian (semua tanggal dalam rentang, yang kosong = 0) ----
    $stmt = $k->prepare(
        "SELECT p.dibuat_pada::date AS tgl, COUNT(DISTINCT p.id_pesanan) AS transaksi, SUM(d.subtotal) AS omzet
         $dasarItem
         GROUP BY 1 ORDER BY 1"
    );
    $stmt->execute($paramsItem);
    $petaHarian = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $petaHarian[$row['tgl']] = $row;
    }
    $harian = [];
    $selisihHari = (int)((strtotime($f['sampai']) - strtotime($f['dari'])) / 86400);
    if ($selisihHari <= 92) { // batasi supaya grafik tetap terbaca
        for ($i = 0; $i <= $selisihHari; $i++) {
            $tgl = date('Y-m-d', strtotime($f['dari'] . " +$i days"));
            $harian[] = [
                'tgl'       => $tgl,
                'transaksi' => (int)($petaHarian[$tgl]['transaksi'] ?? 0),
                'omzet'     => (float)($petaHarian[$tgl]['omzet'] ?? 0),
            ];
        }
    } else {
        foreach ($petaHarian as $tgl => $row) {
            $harian[] = ['tgl' => $tgl, 'transaksi' => (int)$row['transaksi'], 'omzet' => (float)$row['omzet']];
        }
    }

    return [
        'pesanan'      => $pesanan,
        'ringkasan'    => $ringkasan,
        'per_kategori' => $perKategori,
        'per_status'   => $perStatus,
        'terlaris'     => $terlaris,
        'harian'       => $harian,
    ];
}

function laporan_label_periode(array $f): string
{
    $fmt = fn($t) => date('d/m/Y', strtotime($t));
    return $f['dari'] === $f['sampai'] ? $fmt($f['dari']) : $fmt($f['dari']) . ' – ' . $fmt($f['sampai']);
}
