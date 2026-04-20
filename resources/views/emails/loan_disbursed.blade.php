<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dana Pinjaman Dicairkan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #dddddd; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 1px solid #eeeeee; }
        .content { padding: 30px; line-height: 1.6; color: #333333; }
        .badge { display: inline-block; background-color: #dbeafe; color: #1d4ed8; font-weight: bold; padding: 6px 16px; border-radius: 20px; font-size: 14px; margin-bottom: 16px; }
        .amount { font-size: 28px; font-weight: bold; color: #22c55e; text-align: center; margin: 15px 0; }
        .info-box { background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px 20px; border-radius: 4px; margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e0e0e0; }
        .info-row:last-child { border-bottom: none; }
        .label { color: #666; font-size: 14px; }
        .value { font-weight: bold; color: #333; font-size: 14px; }
        .footer { background-color: #f4f4f4; color: #888888; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div style="display:none; max-height:0; overflow:hidden;">{{ $preheader ?? 'Dana pinjaman Anda telah dicairkan ke rekening.' }}</div>
    <div class="container">
        <div class="header">
            <h2 style="color: #333; margin: 0;">A2U Bank Digital</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $full_name }}</strong>,</p>
            <span class="badge">💰 Dana Dicairkan</span>
            <p>Dana pinjaman Anda telah berhasil dicairkan ke rekening tabungan Anda.</p>
            <div class="amount">+{{ $loan_amount }}</div>
            <div class="info-box">
                <div class="info-row">
                    <span class="label">Jumlah Pinjaman</span>
                    <span class="value">{{ $loan_amount }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Tanggal Pencairan</span>
                    <span class="value">{{ now()->format('d M Y H:i:s') }}</span>
                </div>
            </div>
            <p>Pastikan Anda membayar angsuran tepat waktu untuk menghindari denda keterlambatan. Jadwal angsuran dapat dilihat di aplikasi A2U Bank Digital.</p>
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
