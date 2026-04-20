<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjaman Disetujui</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #dddddd; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 1px solid #eeeeee; }
        .content { padding: 30px; line-height: 1.6; color: #333333; }
        .badge { display: inline-block; background-color: #dcfce7; color: #16a34a; font-weight: bold; padding: 6px 16px; border-radius: 20px; font-size: 14px; margin-bottom: 16px; }
        .info-box { background-color: #f0fff4; border-left: 4px solid #22c55e; padding: 15px 20px; border-radius: 4px; margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e0e0e0; }
        .info-row:last-child { border-bottom: none; }
        .label { color: #666; font-size: 14px; }
        .value { font-weight: bold; color: #333; font-size: 14px; }
        .footer { background-color: #f4f4f4; color: #888888; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div style="display:none; max-height:0; overflow:hidden;">{{ $preheader ?? 'Pengajuan pinjaman Anda telah disetujui.' }}</div>
    <div class="container">
        <div class="header">
            <h2 style="color: #333; margin: 0;">A2U Bank Digital</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $full_name }}</strong>,</p>
            <span class="badge">✓ Pinjaman Disetujui</span>
            <p>Selamat! Pengajuan pinjaman Anda telah <strong>disetujui</strong>. Dana akan segera dicairkan ke rekening tabungan Anda.</p>
            <div class="info-box">
                <div class="info-row">
                    <span class="label">Jumlah Pinjaman</span>
                    <span class="value">{{ $loan_amount }}</span>
                </div>
            </div>
            <p>Mohon tunggu proses pencairan dana. Anda akan mendapatkan notifikasi setelah dana berhasil dicairkan.</p>
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
