<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #dddddd;
        }

        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #eeeeee;
        }

        .content {
            padding: 30px;
            line-height: 1.6;
            color: #333333;
        }

        .footer {
            background-color: #f4f4f4;
            color: #888888;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }

        .verify-button {
            display: block;
            width: fit-content;
            margin: 25px auto;
            background-color: #00AEEF;
            color: #ffffff;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
        }

        .verify-button:hover {
            background-color: #0096d1;
        }

        .link-fallback {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            color: #888888;
            word-break: break-all;
        }

        .link-fallback a {
            color: #00AEEF;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div style="display:none; max-height:0; overflow:hidden;">{{ $preheader ?? 'Klik link untuk mengaktifkan akun Anda di A2U Bank Digital.' }}</div>
    <div class="container">
        <div class="header">
            <h2 style="color: #333; margin: 0;">A2U Bank Digital</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $full_name }}</strong>,</p>
            <p>Terima kasih telah mendaftar di A2U Bank Digital. Silakan klik tombol di bawah ini untuk memverifikasi email Anda dan mengaktifkan akun:</p>
            <a href="{{ $verification_url }}" class="verify-button">Verifikasi Email Saya</a>
            <div class="link-fallback">
                <p>Tombol tidak bekerja? Salin link berikut ke browser Anda:</p>
                <p><a href="{{ $verification_url }}">{{ $verification_url }}</a></p>
            </div>
            <p style="margin-top: 20px;">Link ini akan kedaluwarsa dalam <strong>10 menit</strong>. Mohon untuk tidak membagikan link ini kepada siapa pun demi keamanan akun Anda.</p>
            <p>Jika Anda tidak merasa melakukan pendaftaran ini, silakan abaikan email ini.</p>
            <br>
            <p>Hormat kami,</p>
            <p><strong>Tim A2U Bank Digital</strong></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} A2U Bank Digital. Semua hak cipta dilindungi.</p>
            <p>Ini adalah email otomatis, mohon untuk tidak membalas.</p>
        </div>
    </div>
</body>

</html>
