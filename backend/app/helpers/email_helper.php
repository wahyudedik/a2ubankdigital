<?php
// File: app/helpers/email_helper.php
// Penjelasan: Helper terpusat untuk mengirim email menggunakan template.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Pastikan config.php sudah di-include sebelum memanggil fungsi ini
// karena kita butuh variabel dari .env

function send_email($to_email, $to_name, $subject, $template_name, $template_data) {
    $mail = new PHPMailer(true);

    try {
        // --- Server settings ---
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $mail->Port       = $_ENV['MAIL_PORT'];

        // --- Recipients ---
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($to_email, $to_name);

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = $subject;

        // --- Load and Render Template ---
        // 1. Muat template dasar (kerangka)
        $base_template_path = __DIR__ . '/../templates/email/base_template.html';
        if (!file_exists($base_template_path)) {
            throw new Exception("Template dasar tidak ditemukan.");
        }
        $email_body = file_get_contents($base_template_path);

        // 2. Muat template konten spesifik (misal: OTP)
        $content_template_path = __DIR__ . '/../templates/email/' . $template_name . '.html';
        if (!file_exists($content_template_path)) {
            throw new Exception("Template konten '$template_name' tidak ditemukan.");
        }
        $content_html = file_get_contents($content_template_path);

        // 3. Gabungkan data ke template konten
        foreach ($template_data as $key => $value) {
            $content_html = str_replace('{{' . $key . '}}', htmlspecialchars($value), $content_html);
        }

        // 4. Masukkan konten yang sudah jadi ke template dasar
        $email_body = str_replace('{{title}}', $subject, $email_body);
        $email_body = str_replace('{{content}}', $content_html, $email_body);
        // Tambahan data untuk base template
        $email_body = str_replace('{{year}}', date('Y'), $email_body);
        $email_body = str_replace('{{preheader}}', ($template_data['preheader'] ?? 'Notifikasi Penting'), $email_body);


        $mail->Body = $email_body;
        // Opsional: versi plain text untuk email client yang tidak support HTML
        $mail->AltBody = 'Ini adalah email penting. Silakan aktifkan mode HTML untuk melihatnya.';

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Sebaiknya log error ini ke file di mode produksi
        // error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
