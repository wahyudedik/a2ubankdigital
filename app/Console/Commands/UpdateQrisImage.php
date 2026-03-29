<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateQrisImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qris:update {--url= : URL gambar QRIS baru}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update QRIS image URL di system configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->option('url');

        if (!$url) {
            // Gunakan gambar lokal HD dari public directory
            $url = '/qris.jpeg';
            $this->info('Menggunakan gambar lokal: ' . $url);
        }

        try {
            // Update atau insert QRIS image URL
            DB::table('system_configurations')->updateOrInsert(
                ['config_key' => 'payment_qris_image_url'],
                [
                    'config_value' => $url,
                    'description' => 'URL gambar QRIS untuk pembayaran (HD, tidak ter-compress)',
                    'updated_at' => now()
                ]
            );

            $this->info('✓ QRIS image URL berhasil diupdate!');
            $this->newLine();
            $this->info('URL: ' . $url);
            $this->newLine();
            $this->info('Jalankan: php artisan config:cache');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
