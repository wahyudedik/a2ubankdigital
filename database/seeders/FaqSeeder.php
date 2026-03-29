<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'category' => 'ACCOUNT',
                'question' => 'Bagaimana cara membuka rekening di A2U Bank Digital?',
                'answer' => 'Anda dapat membuka rekening dengan mengunjungi halaman registrasi di website kami atau datang langsung ke kantor cabang terdekat. Proses pembukaan rekening online memerlukan KTP, NPWP (opsional), dan foto selfie untuk verifikasi.',
                'is_active' => true
            ],
            [
                'category' => 'ACCOUNT',
                'question' => 'Apa saja persyaratan untuk membuka rekening?',
                'answer' => 'Persyaratan pembukaan rekening: (1) WNI berusia minimal 17 tahun, (2) KTP yang masih berlaku, (3) NPWP (untuk setoran awal di atas Rp 50 juta), (4) Setoran awal minimum Rp 100.000.',
                'is_active' => true
            ],
            [
                'category' => 'TRANSFER',
                'question' => 'Bagaimana cara melakukan transfer ke rekening bank lain?',
                'answer' => 'Login ke akun Anda, pilih menu Transfer, pilih "Transfer ke Bank Lain", masukkan kode bank tujuan, nomor rekening, nama penerima, dan jumlah transfer. Biaya admin Rp 6.500 per transaksi.',
                'is_active' => true
            ],
            [
                'category' => 'TRANSFER',
                'question' => 'Berapa limit transfer per hari?',
                'answer' => 'Limit transfer harian: Transfer internal (sesama A2U Bank) unlimited, Transfer ke bank lain maksimal Rp 50.000.000 per hari, Transfer menggunakan ATM maksimal Rp 20.000.000 per hari.',
                'is_active' => true
            ],
            [
                'category' => 'TRANSFER',
                'question' => 'Apakah bisa membuat transfer terjadwal?',
                'answer' => 'Ya, Anda dapat membuat transfer terjadwal melalui menu "Transfer Terjadwal". Anda bisa mengatur frekuensi transfer (harian, mingguan, atau bulanan) dan tanggal eksekusi. Transfer akan diproses otomatis sesuai jadwal yang Anda tentukan.',
                'is_active' => true
            ],
            [
                'category' => 'LOAN',
                'question' => 'Bagaimana cara mengajukan pinjaman?',
                'answer' => 'Login ke akun Anda, pilih menu "Pinjaman", pilih produk pinjaman yang sesuai, isi formulir pengajuan dengan lengkap, upload dokumen pendukung (KTP, slip gaji, dll), dan tunggu proses verifikasi maksimal 3 hari kerja.',
                'is_active' => true
            ],
            [
                'category' => 'LOAN',
                'question' => 'Apa saja persyaratan pengajuan pinjaman?',
                'answer' => 'Persyaratan pinjaman: (1) Nasabah A2U Bank minimal 6 bulan, (2) Usia 21-55 tahun, (3) Memiliki penghasilan tetap, (4) KTP dan NPWP, (5) Slip gaji 3 bulan terakhir, (6) Rekening koran 3 bulan terakhir.',
                'is_active' => true
            ],
            [
                'category' => 'LOAN',
                'question' => 'Bagaimana cara membayar angsuran pinjaman?',
                'answer' => 'Pembayaran angsuran dapat dilakukan melalui: (1) Auto-debit dari rekening tabungan, (2) Transfer manual ke rekening pinjaman, (3) Setor tunai di teller, (4) Mobile banking. Pastikan membayar sebelum tanggal jatuh tempo untuk menghindari denda.',
                'is_active' => true
            ],
            [
                'category' => 'CARD',
                'question' => 'Bagaimana cara mengajukan kartu debit/kredit?',
                'answer' => 'Login ke akun Anda, pilih menu "Kartu", klik "Ajukan Kartu Baru", pilih jenis kartu yang diinginkan, isi formulir pengajuan. Kartu akan dikirim ke alamat Anda dalam 7-14 hari kerja setelah disetujui.',
                'is_active' => true
            ],
            [
                'category' => 'CARD',
                'question' => 'Apa yang harus dilakukan jika kartu hilang?',
                'answer' => 'Segera lakukan pemblokiran kartu melalui: (1) Mobile banking menu "Kartu" > "Blokir Kartu", (2) Call center 1500-XXX (24 jam), (3) Datang ke kantor cabang terdekat. Setelah diblokir, Anda dapat mengajukan penggantian kartu.',
                'is_active' => true
            ],
            [
                'category' => 'SECURITY',
                'question' => 'Bagaimana cara mengamankan akun saya?',
                'answer' => 'Tips keamanan akun: (1) Jangan bagikan password dan PIN ke siapapun, (2) Aktifkan 2FA (Two-Factor Authentication), (3) Ganti password secara berkala, (4) Jangan gunakan WiFi publik untuk transaksi, (5) Logout setelah selesai bertransaksi.',
                'is_active' => true
            ],
            [
                'category' => 'SECURITY',
                'question' => 'Apa itu 2FA dan bagaimana cara mengaktifkannya?',
                'answer' => '2FA (Two-Factor Authentication) adalah lapisan keamanan tambahan yang memerlukan kode verifikasi selain password. Aktifkan melalui menu "Pengaturan" > "Keamanan" > "Aktifkan 2FA". Anda akan menerima kode OTP via SMS setiap kali login.',
                'is_active' => true
            ],
            [
                'category' => 'DEPOSIT',
                'question' => 'Apa itu deposito dan bagaimana cara membukanya?',
                'answer' => 'Deposito adalah produk simpanan berjangka dengan bunga lebih tinggi dari tabungan. Cara membuka: Login > menu "Deposito" > "Buka Deposito Baru" > pilih produk dan tenor > transfer dana. Minimum deposito Rp 1.000.000.',
                'is_active' => true
            ],
            [
                'category' => 'DEPOSIT',
                'question' => 'Apakah deposito bisa dicairkan sebelum jatuh tempo?',
                'answer' => 'Deposito dapat dicairkan sebelum jatuh tempo (pencairan dini) dengan konsekuensi: (1) Bunga yang sudah diterima akan dipotong, (2) Dikenakan penalti 1-3% dari nominal deposito, (3) Proses pencairan 1-3 hari kerja.',
                'is_active' => true
            ],
            [
                'category' => 'GENERAL',
                'question' => 'Bagaimana cara menghubungi customer service?',
                'answer' => 'Anda dapat menghubungi kami melalui: (1) Call center 1500-XXX (24 jam), (2) Email: cs@a2ubank.com, (3) Live chat di website, (4) WhatsApp: 0812-XXXX-XXXX, (5) Buat tiket dukungan di menu "Bantuan".',
                'is_active' => true
            ],
            [
                'category' => 'GENERAL',
                'question' => 'Jam operasional bank berapa?',
                'answer' => 'Jam operasional kantor cabang: Senin-Jumat 08:00-16:00, Sabtu 08:00-12:00, Minggu & hari libur tutup. Layanan mobile banking dan ATM tersedia 24/7. Call center tersedia 24 jam setiap hari.',
                'is_active' => true
            ],
            [
                'category' => 'FEES',
                'question' => 'Berapa biaya administrasi bulanan?',
                'answer' => 'Biaya administrasi bulanan: Tabungan reguler Rp 10.000/bulan, Tabungan premium Rp 25.000/bulan (gratis jika saldo rata-rata > Rp 5 juta), Giro Rp 50.000/bulan. Deposito tidak dikenakan biaya admin.',
                'is_active' => true
            ],
            [
                'category' => 'FEES',
                'question' => 'Berapa biaya transfer dan tarik tunai?',
                'answer' => 'Biaya transaksi: Transfer internal gratis, Transfer ke bank lain Rp 6.500, Tarik tunai di ATM A2U gratis, Tarik tunai di ATM bank lain Rp 5.000, Tarik tunai di teller gratis (minimal Rp 100.000).',
                'is_active' => true
            ],
            [
                'category' => 'TECHNICAL',
                'question' => 'Aplikasi mobile banking tidak bisa login, apa yang harus dilakukan?',
                'answer' => 'Solusi masalah login: (1) Pastikan username dan password benar, (2) Cek koneksi internet, (3) Update aplikasi ke versi terbaru, (4) Clear cache aplikasi, (5) Restart HP, (6) Jika masih gagal, reset password melalui "Lupa Password" atau hubungi call center.',
                'is_active' => true
            ],
            [
                'category' => 'TECHNICAL',
                'question' => 'Transaksi gagal tapi saldo sudah terpotong, bagaimana?',
                'answer' => 'Jika transaksi gagal tapi saldo terpotong: (1) Tunggu 1x24 jam, biasanya saldo akan dikembalikan otomatis, (2) Cek riwayat transaksi untuk memastikan status, (3) Jika lebih dari 24 jam belum kembali, hubungi call center dengan membawa bukti transaksi.',
                'is_active' => true
            ],
        ];

        foreach ($faqs as $faq) {
            DB::table('faqs')->insert(array_merge($faq, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
