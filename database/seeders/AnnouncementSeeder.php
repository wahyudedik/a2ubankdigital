<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $announcements = [
            [
                'title' => 'Selamat Datang di A2U Bank Digital!',
                'content' => 'Terima kasih telah mempercayai A2U Bank Digital sebagai partner keuangan Anda. Nikmati kemudahan bertransaksi kapan saja, dimana saja dengan fitur-fitur lengkap kami.',
                'type' => 'info',
                'start_date' => now()->subDays(30),
                'end_date' => now()->addMonths(6),
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Promo Bunga Deposito 6% per Tahun!',
                'content' => 'Dapatkan bunga deposito hingga 6% per tahun untuk tenor 12 bulan. Promo berlaku untuk pembukaan deposito baru minimal Rp 10 juta. Periode promo: 1-31 Maret 2026. Syarat dan ketentuan berlaku.',
                'type' => 'promo',
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(15),
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Maintenance Sistem - 1 April 2026',
                'content' => 'Akan dilakukan maintenance sistem pada tanggal 1 April 2026 pukul 01:00 - 05:00 WIB. Selama maintenance, layanan mobile banking dan internet banking tidak dapat diakses. Mohon maaf atas ketidaknyamanannya.',
                'type' => 'warning',
                'start_date' => now()->addDays(2),
                'end_date' => now()->addDays(3),
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Fitur Baru: Transfer Terjadwal',
                'content' => 'Kini Anda dapat membuat transfer terjadwal untuk pembayaran rutin seperti cicilan, tagihan, atau kiriman uang bulanan. Atur sekali, transfer otomatis sesuai jadwal. Coba sekarang di menu Transfer Terjadwal!',
                'type' => 'info',
                'start_date' => now()->subDays(7),
                'end_date' => now()->addMonths(1),
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Peningkatan Keamanan: Aktifkan 2FA',
                'content' => 'Untuk keamanan akun Anda, kami sangat menyarankan untuk mengaktifkan Two-Factor Authentication (2FA). Dengan 2FA, akun Anda akan lebih aman dari akses tidak sah. Aktifkan sekarang di menu Pengaturan > Keamanan.',
                'type' => 'warning',
                'start_date' => now()->subDays(20),
                'end_date' => now()->addMonths(3),
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Libur Nasional: Layanan Terbatas',
                'content' => 'Dalam rangka Hari Raya Idul Fitri, kantor cabang kami akan tutup pada tanggal 10-14 April 2026. Layanan mobile banking, internet banking, dan ATM tetap beroperasi 24/7. Call center beroperasi dengan jam terbatas.',
                'type' => 'info',
                'start_date' => now()->addDays(10),
                'end_date' => now()->addDays(20),
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Cashback 50% untuk Transaksi QRIS!',
                'content' => 'Dapatkan cashback 50% maksimal Rp 10.000 untuk setiap transaksi menggunakan QRIS A2U Bank. Promo berlaku untuk 100 transaksi pertama setiap harinya. Periode: 1-30 April 2026.',
                'type' => 'promo',
                'start_date' => now()->addDays(1),
                'end_date' => now()->addMonths(1),
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Update Aplikasi Mobile Banking v2.5.0',
                'content' => 'Versi terbaru aplikasi mobile banking telah tersedia! Update sekarang untuk menikmati fitur baru: (1) Tampilan lebih modern, (2) Fitur biometrik login, (3) Notifikasi real-time, (4) Performa lebih cepat. Download di Play Store atau App Store.',
                'type' => 'info',
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(25),
                'is_active' => true,
                'created_by' => 1
            ],
        ];

        foreach ($announcements as $announcement) {
            DB::table('announcements')->insert(array_merge($announcement, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
