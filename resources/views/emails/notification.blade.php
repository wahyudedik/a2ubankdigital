<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #dddddd; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 1px solid #eeeeee; }
        .content { padding: 30px; line-height: 1.6; color: #333333; }
        .message-box { background-color: #f8f9fa; border-radius: 6px; padding: 20px; margin: 20px 0; font-size: 15px; color: #333; }
        .footer { background-color: #f4f4f4; color: #888888; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div style="display:none; max-height:0; overflow:hidden;">{{ $preheader ?? 'Notifikasi dari A2U Bank Digital.' }}</div>
    <div class="container">
        <div class="header">
            <h2 style="color: #333; margin: 0;">A2U Bank Digital</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $full_name }}</strong>,</p>
            <div class="message-box">{{ $message }}</div>
            <p>Jika Anda memiliki pertanyaan, silakan hubungi Customer Service kami.</p>
            <br>
            <p>Hormat kami,<br><strong>Tim A2U Bank Digital</strong></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} A2U Bank Digital. Semua hak cipta dilindungi.</p>
            <p>Ini adalah email otomatis, mohon untuk tidak membalas.</p>
        </div>
    </div>
</body>
</html>
