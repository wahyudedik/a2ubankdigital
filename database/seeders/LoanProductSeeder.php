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
                'id' => 1,
                'product_name' => 'Pinjaman 4 Minggu',
                'product_code' => 'LOAN-4W',
                'min_amount' => 100000.00,
                'max_amount' => 500000.00,
                'interest_rate_pa' => 85.00,
                'tenor_unit' => 'MINGGU',
                'min_tenor' => 4,
                'max_tenor' => 4,
                'late_payment_fee' => 3000.00,
                'description' => 'Pinjaman jangka sangat pendek 4 minggu',
                'is_active' => true
            ],
            [
                'id' => 2,
                'product_name' => 'Pinjaman 8 Minggu',
                'product_code' => 'LOAN-8W',
                'min_amount' => 300000.00,
                'max_amount' => 1000000.00,
                'interest_rate_pa' => 85.00,
                'tenor_unit' => 'MINGGU',
                'min_tenor' => 8,
                'max_tenor' => 8,
                'late_payment_fee' => 3000.00,
                'description' => 'Pinjaman jangka pendek 8 minggu',
                'is_active' => true
            ],
            [
                'id' => 3,
                'product_name' => 'Pinjaman 12 Minggu',
                'product_code' => 'LOAN-12W',
                'min_amount' => 500000.00,
                'max_amount' => 2000000.00,
                'interest_rate_pa' => 85.00,
                'tenor_unit' => 'MINGGU',
                'min_tenor' => 12,
                'max_tenor' => 12,
                'late_payment_fee' => 3000.00,
                'description' => 'Pinjaman jangka pendek 12 minggu',
                'is_active' => true
            ],
            [
                'id' => 4,
                'product_name' => 'Pinjaman 3 Bulan',
                'product_code' => 'LOAN-3M',
                'min_amount' => 300000.00,
                'max_amount' => 600000.00,
                'interest_rate_pa' => 85.00,
                'tenor_unit' => 'BULAN',
                'min_tenor' => 1,
                'max_tenor' => 3,
                'late_payment_fee' => 3000.00,
                'description' => 'Pinjaman jangka pendek 3 bulan',
                'is_active' => true
            ],
            [
                'id' => 5,
                'product_name' => 'Pinjaman 12 Minggu',
                'product_code' => 'LOAN-12W-V2',
                'min_amount' => 300000.00,
                'max_amount' => 600000.00,
                'interest_rate_pa' => 250.00,
                'tenor_unit' => 'BULAN',
                'min_tenor' => 1,
                'max_tenor' => 3,
                'late_payment_fee' => 0.00,
                'description' => 'Pinjaman jangka pendek 12 minggu (3 bulan)',
                'is_active' => true
            ],
            [
                'id' => 6,
                'product_name' => 'Pinjaman 8 Minggu (Bulan)',
                'product_code' => 'LOAN-8W-M',
                'min_amount' => 150000.00,
                'max_amount' => 300000.00,
                'interest_rate_pa' => 200.00,
                'tenor_unit' => 'BULAN',
                'min_tenor' => 1,
                'max_tenor' => 2,
                'late_payment_fee' => 0.00,
                'description' => 'Pinjaman jangka pendek 8 minggu (2 bulan)',
                'is_active' => true
            ],
            [
                'id' => 8,
                'product_name' => 'Pinjaman 12 Minggu (Bulan)',
                'product_code' => 'LOAN-12W-M',
                'min_amount' => 300000.00,
                'max_amount' => 600000.00,
                'interest_rate_pa' => 250.00,
                'tenor_unit' => 'BULAN',
                'min_tenor' => 1,
                'max_tenor' => 3,
                'late_payment_fee' => 0.00,
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
                'late_payment_fee' => 0.00,
                'description' => 'Pinjaman jangka menengah 24 minggu (6 bulan)',
                'is_active' => true
            ],
            [
                'id' => 18,
                'product_name' => 'Pinjaman 4 Minggu (Bulan)',
                'product_code' => 'LOAN-4W-M',
                'min_amount' => 10000.00,
                'max_amount' => 150000.00,
                'interest_rate_pa' => 500.00,
                'tenor_unit' => 'BULAN',
                'min_tenor' => 1,
                'max_tenor' => 1,
                'late_payment_fee' => 0.00,
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
