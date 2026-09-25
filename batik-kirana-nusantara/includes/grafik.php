<?php
/**
 * Grafik sederhana tanpa library: SVG (batang vertikal) dan HTML/CSS
 * (batang horizontal). Ikut tema, responsif, dan ikut tercetak.
 */

function grafik_angka_singkat(float $v): string
{
    if ($v >= 1000000) return rtrim(rtrim(number_format($v / 1000000, 1, ',', ''), '0'), ',') . ' jt';
    if ($v >= 1000)    return rtrim(rtrim(number_format($v / 1000, 1, ',', ''), '0'), ',') . ' rb';
    return (string)(int)$v;
}

/** Batang vertikal. $labels & $nilai sama panjang. */
function grafik_batang(array $labels, array $nilai, string $satuan = 'Rp'): string
{
    $n = count($nilai);
    if ($n === 0) return '<div class="empty-state">Belum ada data untuk ditampilkan.</div>';

    $W = 720; $H = 280; $kiri = 58; $kanan = 10; $atas = 14; $bawah = 40;
    $lebar = $W - $kiri - $kanan;
    $tinggi = $H - $atas - $bawah;

    $maks = max($nilai);
    if ($maks <= 0) {
        return '<div class="empty-state">Belum ada penjualan pada periode ini.</div>';
    }
    $kasar = $maks / 4;
    $mag = pow(10, floor(log10($kasar)));
    $rasio = $kasar / $mag;
    $langkah = ($rasio <= 1 ? 1 : ($rasio <= 2 ? 2 : ($rasio <= 5 ? 5 : 10))) * $mag;
    $puncak = ceil($maks / $langkah) * $langkah;

    $svg = '<svg viewBox="0 0 ' . $W . ' ' . $H . '" width="100%" role="img" aria-label="Grafik batang" style="display:block;max-width:100%;height:auto;">';

    for ($v = 0; $v <= $puncak + 0.0001; $v += $langkah) {
        $y = $atas + $tinggi - ($v / $puncak) * $tinggi;
        $svg .= '<line x1="' . $kiri . '" x2="' . ($W - $kanan) . '" y1="' . round($y, 1) . '" y2="' . round($y, 1) . '" stroke="rgba(43,33,24,0.15)" stroke-width="1"/>';
        $svg .= '<text x="' . ($kiri - 8) . '" y="' . round($y + 4, 1) . '" text-anchor="end" font-size="11" fill="#7A4B2A">' . grafik_angka_singkat($v) . '</text>';
    }

    $slot = $lebar / $n;
    $lebarBatang = min(38, $slot * 0.62);
    $lewat = (int)ceil($n / 12);

    foreach ($nilai as $i => $v) {
        $tinggiBatang = ($v / $puncak) * $tinggi;
        $x = $kiri + $slot * $i + ($slot - $lebarBatang) / 2;
        $y = $atas + $tinggi - $tinggiBatang;
        $judul = h($labels[$i]) . ': ' . ($satuan === 'Rp' ? 'Rp ' . number_format($v, 0, ',', '.') : number_format($v, 0, ',', '.') . ' ' . $satuan);
        $svg .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($lebarBatang, 1) . '" height="' . round(max($tinggiBatang, $v > 0 ? 2 : 0), 1) . '" rx="2" fill="#1F3A5A"><title>' . $judul . '</title></rect>';
        if ($i % $lewat === 0) {
            $svg .= '<text x="' . round($x + $lebarBatang / 2, 1) . '" y="' . ($H - 16) . '" text-anchor="middle" font-size="11" fill="#2B2118">' . h($labels[$i]) . '</text>';
        }
    }

    return $svg . '</svg>';
}

/** Batang horizontal. $baris: [['label'=>..., 'nilai'=>float, 'teks'=>string], ...] */
function grafik_horizontal(array $baris): string
{
    if (empty($baris)) return '<div class="empty-state">Belum ada data untuk ditampilkan.</div>';
    $maks = max(array_column($baris, 'nilai'));
    if ($maks <= 0) $maks = 1;
    $out = '<div class="hbar">';
    foreach ($baris as $b) {
        $persen = max(2, round($b['nilai'] / $maks * 100, 1));
        $out .= '<div class="hbar-row"><span class="hbar-label" title="' . h($b['label']) . '">' . h($b['label']) . '</span>'
              . '<span class="hbar-track"><span class="hbar-fill" style="width:' . $persen . '%"></span></span>'
              . '<span class="hbar-nilai">' . h($b['teks']) . '</span></div>';
    }
    return $out . '</div>';
}
