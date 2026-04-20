<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerProfileSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('customer_profiles')->insert([
            [
                'user_id' => 85,
                'unit_id' => 3, // Unit Layanan Jakarta 1
                'nik' => '1207022503960006',
                'mother_maiden_name' => 'Wasiyana',
                'pob' => 'Tanjung Morawa',
                'dob' => '1996-03-25',
                'gender' => 'L',
                'address_ktp' => 'DSN I TELAGA SARI',
                'ktp_image_path' => null,
                'selfie_image_path' => null,
                'address_domicile' => null,
                'occupation' => null,
                'kyc_status' => 'VERIFIED',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 89,
                'unit_id' => 5, // Unit Layanan Jakarta 2
                'nik' => '6401060210930005',
                'mother_maiden_name' => 'Lisnawati',
                'pob' => 'Long Ikis',
                'dob' => '1993-10-02',
                'gender' => 'L',
                'address_ktp' => 'Batu Kajang RT 15',
                'ktp_image_path' => null,
                'selfie_image_path' => null,
                'address_domicile' => null,
                'occupation' => null,
                'kyc_status' => 'VERIFIED',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 90,
                'unit_id' => 6, // Unit Layanan Surabaya 1
                'nik' => '1210190403950004',
                'mother_maiden_name' => 'Sumiaten',
                'pob' => 'Sei Lumut Dusun 1',
                'dob' => '1995-03-04',
                'gender' => 'L',
                'address_ktp' => 'Sei Lumut Dusun 1',
                'ktp_image_path' => null,
                'selfie_image_path' => null,
                'address_domicile' => null,
                'occupation' => null,
                'kyc_status' => 'VERIFIED',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 93,
                'unit_id' => 8, // Unit Layanan Bandung 1
                'nik' => '1207321010970003',
                'mother_maiden_name' => 'Juliani',
                'pob' => 'Rantau Panjang',
                'dob' => '1997-10-10',
                'gender' => 'L',
                'address_ktp' => 'Dusun 1 Rantau Panjang',
                'ktp_image_path' => null,
                'selfie_image_path' => null,
                'address_domicile' => null,
                'occupation' => null,
                'kyc_status' => 'VERIFIED',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
