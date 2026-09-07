<?php
/**
 * Helper untuk mengirim isi form kontak sebagai email, memakai PHPMailer
 * (diinstall lewat Composer — lihat BACA_INI.md).
 *
 * Kalau PHPMailer belum terinstall, fungsi ini akan diam-diam gagal
 * (return false) tanpa mematikan halaman — pesan tetap aman tersimpan
 * di database lewat kontak.php, cuma salinan emailnya saja yang
 * tidak terkirim sampai PHPMailer dipasang.
 */

require_once __DIR__ . '/email_config.php';

function kirim_email_kontak(string $nama, string $emailPengirim, string $isiPesan): array {
    $autoload = __DIR__ . '/../vendor/autoload.php';

    if (!is_file($autoload)) {
        $pesan = 'PHPMailer belum terinstall. Jalankan "composer require phpmailer/phpmailer" di folder batik-kirana-nusantara.';
        error_log('[kontak] ' . $pesan);
        return ['berhasil' => false, 'alasan' => $pesan];
    }

    require_once $autoload;

    if (SMTP_USERNAME === 'isi-email-gmail-kamu@gmail.com') {
        $pesan = 'includes/email_config.php belum diisi dengan akun Gmail & App Password.';
        error_log('[kontak] ' . $pesan);
        return ['berhasil' => false, 'alasan' => $pesan];
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(SMTP_USERNAME, 'Batik Kirana Nusantara — Form Kontak');
        $mail->addAddress(EMAIL_TUJUAN);
        $mail->addReplyTo($emailPengirim, $nama);

        $mail->Subject = 'Pesan baru dari form kontak — ' . $nama;
        $mail->Body =
            "Ada pesan baru masuk lewat form kontak website Batik Kirana Nusantara.\n\n" .
            "Nama   : {$nama}\n" .
            "Email  : {$emailPengirim}\n\n" .
            "Pesan:\n{$isiPesan}\n\n" .
            "---\n" .
            "Balas email ini langsung untuk membalas ke {$emailPengirim}.";

        $mail->send();
        return ['berhasil' => true, 'alasan' => ''];
    } catch (Exception $e) {
        $alasan = $mail->ErrorInfo ?: $e->getMessage();
        error_log('[kontak] Gagal kirim email: ' . $alasan);
        return ['berhasil' => false, 'alasan' => $alasan];
    }
}
