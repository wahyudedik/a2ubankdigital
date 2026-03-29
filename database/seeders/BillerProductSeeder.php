<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillerProductSeeder extends Seeder
{
    public function run(): void
    {
        $billers = [
            // Listrik
            ['buyer_sku_code' => 'PLN_PREPAID_20', 'product_name' => 'Token PLN 20.000', 'category' => 'LISTRIK', 'brand' => 'PLN', 'type' => 'prepaid', 'price' => 20000, 'desc' => 'Token listrik PLN 20.000', 'is_active' => true],
            ['buyer_sku_code' => 'PLN_PREPAID_50', 'product_name' => 'Token PLN 50.000', 'category' => 'LISTRIK', 'brand' => 'PLN', 'type' => 'prepaid', 'price' => 50000, 'desc' => 'Token listrik PLN 50.000', 'is_active' => true],
            ['buyer_sku_code' => 'PLN_PREPAID_100', 'product_name' => 'Token PLN 100.000', 'category' => 'LISTRIK', 'brand' => 'PLN', 'type' => 'prepaid', 'price' => 100000, 'desc' => 'Token listrik PLN 100.000', 'is_active' => true],
            ['buyer_sku_code' => 'PLN_POSTPAID', 'product_name' => 'Tagihan PLN Postpaid', 'category' => 'LISTRIK', 'brand' => 'PLN', 'type' => 'postpaid', 'price' => 0, 'desc' => 'Pembayaran tagihan listrik PLN', 'is_active' => true],

            // Pulsa
            ['buyer_sku_code' => 'TELKOMSEL_10', 'product_name' => 'Pulsa Telkomsel 10.000', 'category' => 'PULSA', 'brand' => 'Telkomsel', 'type' => 'prepaid', 'price' => 10000, 'desc' => 'Pulsa Telkomsel 10.000', 'is_active' => true],
            ['buyer_sku_code' => 'TELKOMSEL_25', 'product_name' => 'Pulsa Telkomsel 25.000', 'category' => 'PULSA', 'brand' => 'Telkomsel', 'type' => 'prepaid', 'price' => 25000, 'desc' => 'Pulsa Telkomsel 25.000', 'is_active' => true],
            ['buyer_sku_code' => 'INDOSAT_10', 'product_name' => 'Pulsa Indosat 10.000', 'category' => 'PULSA', 'brand' => 'Indosat', 'type' => 'prepaid', 'price' => 10000, 'desc' => 'Pulsa Indosat 10.000', 'is_active' => true],
            ['buyer_sku_code' => 'XL_10', 'product_name' => 'Pulsa XL 10.000', 'category' => 'PULSA', 'brand' => 'XL', 'type' => 'prepaid', 'price' => 10000, 'desc' => 'Pulsa XL 10.000', 'is_active' => true],

            // Air
            ['buyer_sku_code' => 'PDAM_JKT', 'product_name' => 'PDAM Jakarta', 'category' => 'AIR', 'brand' => 'PDAM', 'type' => 'postpaid', 'price' => 0, 'desc' => 'Pembayaran tagihan air PDAM Jakarta', 'is_active' => true],
            ['buyer_sku_code' => 'PDAM_BDG', 'product_name' => 'PDAM Bandung', 'category' => 'AIR', 'brand' => 'PDAM', 'type' => 'postpaid', 'price' => 0, 'desc' => 'Pembayaran tagihan air PDAM Bandung', 'is_active' => true],

            // Internet
            ['buyer_sku_code' => 'INDIHOME', 'product_name' => 'IndiHome', 'category' => 'INTERNET', 'brand' => 'Telkom', 'type' => 'postpaid', 'price' => 0, 'desc' => 'Pembayaran tagihan IndiHome', 'is_active' => true],
            ['buyer_sku_code' => 'FIRSTMEDIA', 'product_name' => 'First Media', 'category' => 'INTERNET', 'brand' => 'First Media', 'type' => 'postpaid', 'price' => 0, 'desc' => 'Pembayaran tagihan First Media', 'is_active' => true],

            // BPJS
            ['buyer_sku_code' => 'BPJS_KES', 'product_name' => 'BPJS Kesehatan', 'category' => 'ASURANSI', 'brand' => 'BPJS', 'type' => 'postpaid', 'price' => 0, 'desc' => 'Pembayaran iuran BPJS Kesehatan', 'is_active' => true],

            // TV Kabel
            ['buyer_sku_code' => 'TRANSVISION', 'product_name' => 'Transvision', 'category' => 'TV_KABEL', 'brand' => 'Transvision', 'type' => 'postpaid', 'price' => 0, 'desc' => 'Pembayaran tagihan Transvision', 'is_active' => true],
        ];

        foreach ($billers as $biller) {
            DB::table('biller_products')->insert(array_merge($biller, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
