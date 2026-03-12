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
                'unit_id' => 17,
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
                'unit_id' => 69,
                'nik' => '6401060210930005',
                'mother_maiden_name' => 'Lisnawati',
                'pob' => 'Long ikis',
                'dob' => '1993-10-02',
                'gender' => 'L',
                'address_ktp' => 'Batu kajang rt 15 ',
                'ktp_image_path' => '/uploads/documents/6401060210930005_ktp_image_1772178580.jpg',
                'selfie_image_path' => '/uploads/documents/6401060210930005_selfie_image_1772178580.jpg',
                'address_domicile' => null,
                'occupation' => null,
                'kyc_status' => 'VERIFIED',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 90,
                'unit_id' => 68,
                'nik' => '1210190403950004',
                'mother_maiden_name' => 'Sumiaten',
                'pob' => 'Sei Lumut Dusun 1',
                'dob' => '1995-03-04',
                'gender' => 'L',
                'address_ktp' => 'Sei Lumut Dusun 1',
                'ktp_image_path' => '/uploads/documents/1210190403950004_ktp_image_1772214135.jpeg',
                'selfie_image_path' => '/uploads/documents/1210190403950004_selfie_image_1772214135.jpeg',
                'address_domicile' => null,
                'occupation' => null,
                'kyc_status' => 'VERIFIED',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 93,
                'unit_id' => 3,
                'nik' => '1207321010970003',
                'mother_maiden_name' => 'Juliani ',
                'pob' => 'Rantau panjang ',
                'dob' => '1997-10-10',
                'gender' => 'L',
                'address_ktp' => 'Dusun 1 rantau panjang ',
                'ktp_image_path' => '/uploads/documents/1207321010970003_ktp_image_1772688596.jpg',
                'selfie_image_path' => '/uploads/documents/1207321010970003_selfie_image_1772688596.jpg',
                'address_domicile' => null,
                'occupation' => null,
                'kyc_status' => 'VERIFIED',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
