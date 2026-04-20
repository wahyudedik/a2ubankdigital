<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Transfer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #dddddd; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 1px solid #eeeeee; }
        .content { padding: 30px; line-height: 1.6; color: #333333; }
        .info-box { background-color: #f0f7ff; border-left: 4px solid #00AEEF; padding: 15px 20px; border-radius: 4px; margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e0e0e0; }
        .info-row:last-child { border-bottom: none; }
        .label { color: #666; font-size: 14px; }
        .value { font-weight: bold; color: #333; font-size: 14px; }
        .amount { font-size: 24px; font-weight: bold; color: #00AEEF; text-align: center; margin: 15px 0; }
        .footer { background-color: #f4f4f4; color: #888888; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div style="display:none; max-height:0; overflow:hidden;">{{ $preheader ?? 'Konfirmasi transfer berhasil.' }}</div>
    <div class="container">
        <div class="header">
            <h2 style="color: #333; margin: 0;">A2U Bank Digital</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $full_name }}</strong>,</p>
            <p>Transfer Anda telah berhasil diproses. Berikut detail transaksinya:</p>
            <div class="amount">{{ $amount }}</div>
            <div class="info-box">
                <div class="info-row">
                    <span class="label">Rekening Tujuan</span>
                    <span class="value">{{ $destination_account }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Keterangan</span>
                    <span class="value">{{ $description }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Kode Transaksi</span>
                    <span class="value">{{ $transaction_code }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Tanggal & Waktu</span>
                    <span class="value">{{ now()->format('d M Y H:i:s') }}</span>
                </div>
            </div>
            <p>Jika Anda tidak merasa melakukan transaksi ini, segera hubungi Customer Service kami.</p>
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
