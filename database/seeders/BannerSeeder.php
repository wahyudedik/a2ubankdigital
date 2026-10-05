<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BannerSeeder extends Seeder
{
    /**
     * Seeder contoh banner slider memakai gambar yang sudah ada di public/.
     * Production-safe: tidak dipanggil otomatis dari DatabaseSeeder.
     *
     * Jalankan manual: php artisan db:seed --class=BannerSeeder
     */
    public function run(): void
    {
        if (Banner::count() > 0) {
            $this->command?->info('Banner sudah ada, seeder dilewati.');
            return;
        }

        $banners = [
            ['title' => 'Aplikasi A2U Bank Digital', 'source' => 'app-mockup.png', 'link_url' => null],
            ['title' => 'Pembayaran QRIS', 'source' => 'qris.jpeg', 'link_url' => '/qr-payment'],
            ['title' => 'Kartu Debit A2U', 'source' => 'card.png', 'link_url' => '/profile/cards'],
        ];

        $targetDir = storage_path('app/public/banners');
        File::ensureDirectoryExists($targetDir);

        foreach ($banners as $index => $banner) {
            $source = public_path($banner['source']);
            if (!File::exists($source)) {
                $this->command?->warn("File sumber tidak ditemukan: {$banner['source']}, dilewati.");
                continue;
            }

            $filename = 'banner-' . ($index + 1) . '-' . $banner['source'];
            File::copy($source, $targetDir . DIRECTORY_SEPARATOR . $filename);

            Banner::create([
                'title' => $banner['title'],
                'image_path' => 'banners/' . $filename,
                'link_url' => $banner['link_url'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        $this->command?->info('Banner contoh berhasil dibuat.');
    }
}
