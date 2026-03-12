<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('accounts')->insert([
            [
                'id' => 51,
                'user_id' => 85,
                'account_number' => '1100000085204',
                'account_type' => 'TABUNGAN',
                'balance' => 267329362.69,
                'status' => 'ACTIVE',
                'created_at' => '2026-02-17 15:08:55',
                'updated_at' => '2026-03-01 12:20:35',
                'credit_limit' => null,
                'deposit_product_id' => null,
                'maturity_date' => null,
            ],
            [
                'id' => 54,
                'user_id' => 89,
                'account_number' => '1100000089195',
                'account_type' => 'TABUNGAN',
                'balance' => 0.00,
                'status' => 'ACTIVE',
                'created_at' => '2026-02-27 07:50:08',
                'updated_at' => '2026-02-27 07:50:08',
                'credit_limit' => null,
                'deposit_product_id' => null,
                'maturity_date' => null,
            ],
            [
                'id' => 55,
                'user_id' => 90,
                'account_number' => '1100000090515',
                'account_type' => 'TABUNGAN',
                'balance' => 0.00,
                'status' => 'ACTIVE',
                'created_at' => '2026-02-27 17:43:14',
                'updated_at' => '2026-02-27 17:43:14',
                'credit_limit' => null,
                'deposit_product_id' => null,
                'maturity_date' => null,
            ],
            [
                'id' => 56,
                'user_id' => 93,
                'account_number' => '1100000093214',
                'account_type' => 'TABUNGAN',
                'balance' => 0.00,
                'status' => 'ACTIVE',
                'created_at' => '2026-03-05 05:30:34',
                'updated_at' => '2026-03-05 05:30:34',
                'credit_limit' => null,
                'deposit_product_id' => null,
                'maturity_date' => null,
            ],
        ]);
    }
}
