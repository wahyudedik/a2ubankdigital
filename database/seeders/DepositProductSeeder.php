<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepositProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'id' => 1,
                'product_name' => 'Deposito 12 Bulan',
                'interest_rate_pa' => 5,
                'tenor_months' => 12,
                'min_amount' => 1000000.00,
                'is_active' => true
            ],
            [
                'id' => 2,
                'product_name' => 'Deposito 120 Bulan',
                'interest_rate_pa' => 5,
                'tenor_months' => 120,
                'min_amount' => 100000000.00,
                'is_active' => true
            ],
            [
                'id' => 3,
                'product_name' => 'Deposito 12 Bulan',
                'interest_rate_pa' => 10,
                'tenor_months' => 12,
                'min_amount' => 5000000.00,
                'is_active' => true
            ],
            [
                'id' => 4,
                'product_name' => 'Deposit 12 Bulan',
                'interest_rate_pa' => 7,
                'tenor_months' => 12,
                'min_amount' => 10000000.00,
                'is_active' => true
            ],
            [
                'id' => 85,
                'product_name' => 'Deposito 60 Bulan',
                'interest_rate_pa' => 3,
                'tenor_months' => 60,
                'min_amount' => 50000000.00,
                'is_active' => true
            ],
        ];

        foreach ($products as $product) {
            DB::table('deposit_products')->updateOrInsert(
                ['id' => $product['id']],
                array_merge($product, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}
