<?php
/**
 * Daftar metode pembayaran + sub-pilihan (bank / e-wallet).
 * Dipakai bersama oleh checkout.php dan admin/transaksi_baru.php
 * supaya daftarnya cukup diubah di satu tempat.
 *
 * Nilai yang disimpan ke kolom pesanan.metode_pembayaran berbentuk teks gabungan,
 * misalnya "Transfer Bank - Mandiri" atau "E-Wallet - DANA".
 */

/** Metode yang punya dropdown lanjutan: nama metode => daftar pilihan. */
function metode_sub_pilihan(): array
{
    return [
        'Transfer Bank' => ['BCA', 'Mandiri', 'BRI', 'BNI', 'BSI', 'CIMB Niaga', 'Permata', 'BTN', 'Danamon'],
        'E-Wallet'      => ['DANA', 'OVO', 'GoPay', 'ShopeePay', 'LinkAja'],
    ];
}

/**
 * Gabungkan metode + pilihan lanjutan jadi satu teks untuk disimpan.
 * Return null kalau metode punya sub-pilihan tetapi pilihannya kosong/tidak valid.
 */
function metode_gabung(string $metode, string $detail): ?string
{
    $sub = metode_sub_pilihan();
    if (isset($sub[$metode])) {
        return in_array($detail, $sub[$metode], true) ? $metode . ' - ' . $detail : null;
    }
    return $metode;
}

/** Cetak <select> kedua (bank / e-wallet) + script show/hide, siap ditaruh setelah <select> metode. */
function metode_render_sub(string $idMetode = 'metode_pembayaran', string $detailTerpilih = ''): void
{
    $sub = metode_sub_pilihan();
    echo '<style>.sub-metode[hidden]{display:none !important;}</style>';
    foreach ($sub as $metode => $daftar) {
        $idBox = 'sub-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $metode));
        $label = $metode === 'Transfer Bank' ? 'Pilih Bank' : 'Pilih E-Wallet';
        echo '<div class="form-field sub-metode" id="' . $idBox . '" data-metode="' . htmlspecialchars($metode) . '" hidden>';
        echo '<label for="' . $idBox . '-select">' . $label . '</label>';
        echo '<select id="' . $idBox . '-select" data-sub-select>';
        echo '<option value="">— ' . $label . ' —</option>';
        foreach ($daftar as $opsi) {
            $sel = $detailTerpilih === $opsi ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($opsi) . '"' . $sel . '>' . htmlspecialchars($opsi) . '</option>';
        }
        echo '</select>';
        echo '<small class="form-error" id="error-' . $idBox . '-select"></small>';
        echo '</div>';
    }
    // Field tersembunyi yang benar-benar dikirim ke server
    echo '<input type="hidden" name="metode_detail" id="metode_detail" value="' . htmlspecialchars($detailTerpilih) . '">';
    ?>
    <script>
    (() => {
      const metode = document.getElementById(<?= json_encode($idMetode) ?>);
      const hidden = document.getElementById('metode_detail');
      if (!metode || !hidden) return;
      const boxes = document.querySelectorAll('.sub-metode');
      const sinkron = () => {
        let aktif = null;
        boxes.forEach((box) => {
          const cocok = box.dataset.metode === metode.value;
          box.hidden = !cocok;
          if (cocok) aktif = box.querySelector('select');
          else box.querySelector('select').value = '';
        });
        hidden.value = aktif ? aktif.value : '';
      };
      boxes.forEach((box) => box.querySelector('select').addEventListener('change', sinkron));
      metode.addEventListener('change', () => {
        // ganti metode → reset pilihan lama, lalu tampilkan dropdown yang sesuai
        boxes.forEach((box) => (box.querySelector('select').value = ''));
        sinkron();
      });
      // kondisi awal (mis. setelah form gagal validasi & halaman dimuat ulang)
      boxes.forEach((box) => {
        if (box.dataset.metode === metode.value) box.querySelector('select').value = hidden.value;
      });
      sinkron();
    })();
    </script>
    <?php
}
