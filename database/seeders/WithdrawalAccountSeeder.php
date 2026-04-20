<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WithdrawalAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('withdrawal_accounts')->insert([
            [
                'id' => 1,
                'user_id' => 85,
                'bank_name' => 'BNI',
                'account_number' => '1820375527',
                'account_name' => 'Andre Aldi Utama',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'user_id' => 89,
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'Chandra Budi Setiawan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'user_id' => 90,
                'bank_name' => 'Mandiri',
                'account_number' => '9876543210',
                'account_name' => 'Sahri Mandala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'user_id' => 93,
                'bank_name' => 'BRI',
                'account_number' => '5678901234',
                'account_name' => 'Rizky Pratama',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
