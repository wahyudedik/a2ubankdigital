<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'A2U Bank Digital' }}</title>
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
            color: #ffffff;
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

        .otp-code {
            text-align: center;
            margin: 20px 0;
        }

        .otp-code h2 {
            font-size: 36px;
            letter-spacing: 5px;
            color: #333;
            margin: 0;
        }
    </style>
</head>

<body>
    <!-- Preheader Text -->
    <div style="display:none; max-height:0; overflow:hidden;">
        {{ $preheader ?? 'Notifikasi Penting' }}
    </div>

    <div class="container">
        <div class="header">
            @php
                $logoPath = public_path('a2u-logo.png');
                $logoBase64 = file_exists($logoPath)
                    ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                    : config('app.url') . '/a2u-logo.png';
            @endphp
            <img src="{{ $logoBase64 }}" alt="A2U Bank Digital Logo" style="max-width: 180px; height: auto;" />
        </div>
        <div class="content">
            @yield('content')
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} A2U Bank Digital. Semua hak cipta dilindungi.</p>
            <p>Ini adalah email otomatis, mohon untuk tidak membalas.</p>
        </div>
    </div>
</body>

</html>
