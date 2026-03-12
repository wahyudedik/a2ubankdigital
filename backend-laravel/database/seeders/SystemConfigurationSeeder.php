<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'config_key' => 'APP_DOWNLOAD_LINK_ANDROID',
                'config_value' => '',
                'description' => 'Link download aplikasi Android'
            ],
            [
                'config_key' => 'APP_DOWNLOAD_LINK_IOS',
                'config_value' => '',
                'description' => 'Link download aplikasi iOS'
            ],
            [
                'config_key' => 'monthly_admin_fee',
                'config_value' => '16500',
                'description' => 'Biaya administrasi bulanan untuk rekening tabungan.'
            ],
            [
                'config_key' => 'payment_bank_accounts',
                'config_value' => '[]',
                'description' => 'Daftar rekening bank untuk pembayaran'
            ],
            [
                'config_key' => 'payment_qris_image_url',
                'config_value' => 'https://iili.io/qCq5BsI.jpg',
                'description' => 'URL gambar QRIS untuk pembayaran'
            ],
            [
                'config_key' => 'transfer_fee_external',
                'config_value' => '6500',
                'description' => 'Biaya transfer antar bank.'
            ],
        ];

        foreach ($configs as $config) {
            DB::table('system_configurations')->updateOrInsert(
                ['config_key' => $config['config_key']],
                array_merge($config, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}
