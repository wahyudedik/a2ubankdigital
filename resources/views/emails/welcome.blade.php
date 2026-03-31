<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang</title>
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

        .button {
            display: inline-block;
            background-color: #00AEEF;
            color: #ffffff;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div style="display:none; max-height:0; overflow:hidden;">{{ $preheader ?? 'Selamat datang di A2U Bank Digital.' }}
    </div>
    <div class="container">
        <div class="header">
            <h2 style="color: #333; margin: 0;">A2U Bank Digital</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $full_name }}</strong>,</p>
            <p>Selamat datang di <strong>A2U Bank Digital</strong>! Akun Anda telah berhasil diaktifkan.</p>
            <p>Anda sekarang dapat menikmati berbagai layanan perbankan digital kami:</p>
            <ul>
                <li>Transfer antar rekening</li>
                <li>Pembayaran tagihan</li>
                <li>Pengajuan pinjaman</li>
                <li>Deposito online</li>
                <li>Dan masih banyak lagi</li>
            </ul>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}" class="button">Mulai Sekarang</a>
            </p>
            <p>Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi tim customer service kami.</p>
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
