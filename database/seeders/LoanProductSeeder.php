<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoanProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'id' => 6,
                'product_name' => 'Pinjaman 8 Minggu',
                'product_code' => 'LOAN-8W',
                'min_amount' => 150000.00,
                'max_amount' => 300000.00,
                'interest_rate_pa' => 200.00,
                'tenor_unit' => 'BULAN',
                'min_tenor' => 1,
                'max_tenor' => 2,
                'description' => 'Pinjaman jangka pendek 8 minggu (2 bulan)',
                'is_active' => true
            ],
            [
                'id' => 8,
                'product_name' => 'Pinjaman 12 Minggu',
                'product_code' => 'LOAN-12W',
                'min_amount' => 300000.00,
                'max_amount' => 600000.00,
                'interest_rate_pa' => 250.00,
                'tenor_unit' => 'BULAN',
                'min_tenor' => 1,
                'max_tenor' => 3,
                'description' => 'Pinjaman jangka pendek 12 minggu (3 bulan)',
                'is_active' => true
            ],
            [
                'id' => 17,
                'product_name' => 'Pinjaman 24 Minggu',
                'product_code' => 'LOAN-24W',
                'min_amount' => 600000.00,
                'max_amount' => 1200000.00,
                'interest_rate_pa' => 200.00,
                'tenor_unit' => 'BULAN',
                'min_tenor' => 1,
                'max_tenor' => 6,
                'description' => 'Pinjaman jangka menengah 24 minggu (6 bulan)',
                'is_active' => true
            ],
            [
                'id' => 18,
                'product_name' => 'Pinjaman 4 Minggu',
                'product_code' => 'LOAN-4W',
                'min_amount' => 10000.00,
                'max_amount' => 150000.00,
                'interest_rate_pa' => 500.00,
                'tenor_unit' => 'BULAN',
                'min_tenor' => 1,
                'max_tenor' => 1,
                'description' => 'Pinjaman jangka sangat pendek 4 minggu (1 bulan)',
                'is_active' => true
            ],
        ];

        foreach ($products as $product) {
            DB::table('loan_products')->updateOrInsert(
                ['id' => $product['id']],
                array_merge($product, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}
