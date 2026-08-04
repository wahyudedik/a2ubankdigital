<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create KANTOR_PUSAT (Headquarters)
        $pusat = Unit::create([
            'unit_name' => 'Kantor Pusat A2U Bank',
            'unit_code' => 'HQ-001',
            'unit_type' => 'KANTOR_PUSAT',
            'parent_id' => null,
            'address' => 'Jl. Sudirman No. 1, Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'status' => 'ACTIVE',
        ]);

        // Create KANTOR_CABANG (Branches)
        $branchJakarta = Unit::create([
            'unit_name' => 'Cabang Jakarta',
            'unit_code' => 'JAK-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'address' => 'Jl. Pemuda No. 1, Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'status' => 'ACTIVE',
        ]);

        $branchSurabaya = Unit::create([
            'unit_name' => 'Cabang Surabaya',
            'unit_code' => 'SBY-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'address' => 'Jl. Pemuda No. 1, Surabaya',
            'latitude' => -7.2575,
            'longitude' => 112.7521,
            'status' => 'ACTIVE',
        ]);

        $branchBandung = Unit::create([
            'unit_name' => 'Cabang Bandung',
            'unit_code' => 'BDG-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'address' => 'Jl. Diponegoro No. 1, Bandung',
            'latitude' => -6.9175,
            'longitude' => 107.6062,
            'status' => 'ACTIVE',
        ]);

        // Create KANTOR_KAS (Sub-units) under Jakarta branch
        Unit::create([
            'unit_name' => 'Unit Layanan Jakarta 1',
            'unit_code' => 'JAK-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branchJakarta->id,
            'latitude' => -6.2150,
            'longitude' => 106.8500,
            'address' => 'Jl. Sudirman Kav. 52-53, Jakarta',
            'status' => 'ACTIVE',
        ]);

        Unit::create([
            'unit_name' => 'Unit Layanan Jakarta 2',
            'unit_code' => 'JAK-003',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branchJakarta->id,
            'latitude' => -6.1850,
            'longitude' => 106.8350,
            'address' => 'Jl. Gatot Subroto No. 23, Jakarta',
            'status' => 'ACTIVE',
        ]);

        // Create KANTOR_KAS (Sub-units) under Surabaya branch
        Unit::create([
            'unit_name' => 'Unit Layanan Surabaya 1',
            'unit_code' => 'SBY-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branchSurabaya->id,
            'latitude' => -7.2450,
            'longitude' => 112.7400,
            'address' => 'Jl. Basuki Rachmat No. 45, Surabaya',
            'status' => 'ACTIVE',
        ]);

        Unit::create([
            'unit_name' => 'Unit Layanan Surabaya 2',
            'unit_code' => 'SBY-003',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branchSurabaya->id,
            'latitude' => -7.2700,
            'longitude' => 112.7650,
            'address' => 'Jl. Dharmahusada No. 12, Surabaya',
            'status' => 'ACTIVE',
        ]);

        // Create KANTOR_KAS (Sub-units) under Bandung branch
        Unit::create([
            'unit_name' => 'Unit Layanan Bandung 1',
            'unit_code' => 'BDG-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branchBandung->id,
            'latitude' => -6.9050,
            'longitude' => 107.5950,
            'address' => 'Jl. Asia Afrika No. 68, Bandung',
            'status' => 'ACTIVE',
        ]);

        Unit::create([
            'unit_name' => 'Unit Layanan Bandung 2',
            'unit_code' => 'BDG-003',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branchBandung->id,
            'latitude' => -6.9300,
            'longitude' => 107.6200,
            'address' => 'Jl. Buah Batu No. 150, Bandung',
            'status' => 'ACTIVE',
        ]);
    }
}
