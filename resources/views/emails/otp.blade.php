<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi OTP</title>
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

        .otp-code {
            text-align: center;
            margin: 20px 0;
        }

        .otp-code h2 {
            font-size: 36px;
            letter-spacing: 5px;
            color: #333;
            margin: 0;
            background: #f0f0f0;
            display: inline-block;
            padding: 15px 30px;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div style="display:none; max-height:0; overflow:hidden;">{{ $preheader ?? 'Kode verifikasi pendaftaran Anda.' }}
    </div>
    <div class="container">
        <div class="header">
            <h2 style="color: #333; margin: 0;">A2U Bank Digital</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $full_name }}</strong>,</p>
            <p>Terima kasih telah mendaftar di A2U Bank Digital. Gunakan kode verifikasi (OTP) di bawah ini:</p>
            <div class="otp-code">
                <h2>{{ $otp_code }}</h2>
            </div>
            <p>Kode ini akan kedaluwarsa dalam <strong>10 menit</strong>. Mohon untuk tidak membagikan kode ini kepada
                siapa pun demi keamanan akun Anda.</p>
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
