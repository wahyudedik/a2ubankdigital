<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CardSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cards')->insert([
            [
                'id' => 9,
                'user_id' => 85,
                'account_id' => 51,
                'card_number_masked' => '5123-XXXX-XXXX-7203',
                'card_type' => 'DEBIT',
                'status' => 'ACTIVE',
                'daily_limit' => 10000.00,
                'requested_at' => '2026-02-17 18:25:44',
                'activated_at' => '2026-02-17 18:45:55',
                'expiry_date' => '2031-02-18',
            ],
        ]);
    }
}
