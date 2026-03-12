<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['id' => 1, 'unit_name' => 'Yogyakarta', 'unit_code' => 'YOG', 'unit_type' => 'KANTOR_CABANG', 'address' => 'Daerah istimewa yogyakarta', 'latitude' => -7.79407877, 'longitude' => 110.36905522, 'status' => 'ACTIVE'],
            ['id' => 2, 'unit_name' => 'Jogja Kota', 'unit_code' => 'YOG-01', 'unit_type' => 'KANTOR_KAS', 'address' => 'Jogja Kota', 'latitude' => -7.77860175, 'longitude' => 110.36665052, 'status' => 'ACTIVE'],
            ['id' => 3, 'unit_name' => 'DKI Jakarta', 'unit_code' => 'JKT', 'unit_type' => 'KANTOR_CABANG', 'address' => 'DKI Jakarta', 'latitude' => -6.19456967, 'longitude' => 106.82238246, 'status' => 'ACTIVE'],
            ['id' => 4, 'unit_name' => 'Jakarta Selatan', 'unit_code' => 'JKT-01', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
            ['id' => 5, 'unit_name' => 'Jawa Barat', 'unit_code' => 'JBR', 'unit_type' => 'KANTOR_CABANG', 'address' => '', 'latitude' => 0, 'longitude' => 0, 'status' => 'ACTIVE'],
            ['id' => 9, 'unit_name' => 'Sumatera Utara', 'unit_code' => 'SUT', 'unit_type' => 'KANTOR_CABANG', 'address' => 'Daerah Indonesia', 'latitude' => 4, 'longitude' => 100, 'status' => 'ACTIVE'],
            ['id' => 10, 'unit_name' => 'Deli Serdang', 'unit_code' => 'SUT-DS', 'unit_type' => 'KANTOR_CABANG', 'address' => 'Daerah Sumatera Utara', 'latitude' => 0, 'longitude' => 0, 'status' => 'ACTIVE'],
            ['id' => 11, 'unit_name' => 'Asahan', 'unit_code' => 'SUT-ASH', 'unit_type' => 'KANTOR_CABANG', 'address' => 'Daerah Sumatera Utara', 'latitude' => 0, 'longitude' => 0, 'status' => 'ACTIVE'],
            ['id' => 12, 'unit_name' => 'Dairi', 'unit_code' => 'SUT-01', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
            ['id' => 13, 'unit_name' => 'Humbang Hasundutan', 'unit_code' => 'SUT-02', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
            ['id' => 14, 'unit_name' => 'Batu Bara', 'unit_code' => 'SUT-03', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
            ['id' => 15, 'unit_name' => 'Karo', 'unit_code' => 'SUT-04', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
            ['id' => 16, 'unit_name' => 'Kab.Labuhan Batu', 'unit_code' => 'SUT-LB', 'unit_type' => 'KANTOR_CABANG', 'address' => 'Sumatera Utara', 'latitude' => 0, 'longitude' => 0, 'status' => 'ACTIVE'],
            ['id' => 17, 'unit_name' => 'Tanjung Morawa', 'unit_code' => 'SUT-DS-01', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
            ['id' => 18, 'unit_name' => 'Lubuk Pakam', 'unit_code' => 'SUT-DS-02', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
            ['id' => 19, 'unit_name' => 'Patumbak', 'unit_code' => 'SUT-DS-03', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
            ['id' => 20, 'unit_name' => 'Percut Sei Tuan', 'unit_code' => 'SUT-DS-04', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
            ['id' => 31, 'unit_name' => 'Aceh', 'unit_code' => 'ACH', 'unit_type' => 'KANTOR_CABANG', 'address' => '', 'latitude' => 0, 'longitude' => 0, 'status' => 'ACTIVE'],
            ['id' => 43, 'unit_name' => 'Kab.Paser', 'unit_code' => 'KTM-PSR', 'unit_type' => 'KANTOR_CABANG', 'address' => 'Kalimantan Timur', 'latitude' => 24.21, 'longitude' => 119, 'status' => 'ACTIVE'],
            ['id' => 50, 'unit_name' => 'Kalimantan Timur', 'unit_code' => 'KTM', 'unit_type' => 'KANTOR_CABANG', 'address' => '', 'latitude' => 2.33, 'longitude' => 119, 'status' => 'ACTIVE'],
            ['id' => 67, 'unit_name' => 'Kab.Labuhan Batu Utara', 'unit_code' => 'SUT-LBU', 'unit_type' => 'KANTOR_CABANG', 'address' => 'Sumatera Utara', 'latitude' => 0, 'longitude' => 0, 'status' => 'ACTIVE'],
            ['id' => 68, 'unit_name' => 'Kec.Marbau', 'unit_code' => 'SUT-LBU-01', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
            ['id' => 69, 'unit_name' => 'Kec.Batu Sopang', 'unit_code' => 'KTM-PSR-01', 'unit_type' => 'KANTOR_KAS', 'address' => null, 'latitude' => null, 'longitude' => null, 'status' => 'ACTIVE'],
        ];

        foreach ($units as $unit) {
            DB::table('units')->updateOrInsert(
                ['id' => $unit['id']],
                array_merge($unit, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}
