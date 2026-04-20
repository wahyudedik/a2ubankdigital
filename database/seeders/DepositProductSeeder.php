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
                'product_name' => 'Deposito 1 Bulan',
                'interest_rate_pa' => 5.00,
                'tenor_months' => 1,
                'min_amount' => 1000000.00,
                'max_amount' => null,
                'is_active' => true
            ],
            [
                'id' => 2,
                'product_name' => 'Deposito 3 Bulan',
                'interest_rate_pa' => 6.00,
                'tenor_months' => 3,
                'min_amount' => 1000000.00,
                'max_amount' => null,
                'is_active' => true
            ],
            [
                'id' => 3,
                'product_name' => 'Deposito 6 Bulan',
                'interest_rate_pa' => 6.50,
                'tenor_months' => 6,
                'min_amount' => 5000000.00,
                'max_amount' => null,
                'is_active' => true
            ],
            [
                'id' => 4,
                'product_name' => 'Deposito 12 Bulan',
                'interest_rate_pa' => 7.00,
                'tenor_months' => 12,
                'min_amount' => 10000000.00,
                'max_amount' => null,
                'is_active' => true
            ],
            [
                'id' => 5,
                'product_name' => 'Deposito 24 Bulan',
                'interest_rate_pa' => 7.50,
                'tenor_months' => 24,
                'min_amount' => 50000000.00,
                'max_amount' => null,
                'is_active' => true
            ],
            [
                'id' => 6,
                'product_name' => 'Uji Coba',
                'interest_rate_pa' => 7.00,
                'tenor_months' => 1,
                'min_amount' => 1000000.00,
                'max_amount' => null,
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
