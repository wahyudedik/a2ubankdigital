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
                'id' => 14,
                'user_id' => 85,
                'bank_name' => 'BNI',
                'account_number' => '1820375527',
                'account_name' => 'Andre Aldi Utama',
                'created_at' => '2026-02-17 18:36:03',
            ],
        ]);
    }
}
