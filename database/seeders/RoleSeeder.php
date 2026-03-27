<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'role_name' => 'Super Admin'],
            ['id' => 2, 'role_name' => 'Kepala Cabang'],
            ['id' => 3, 'role_name' => 'Kepala Unit'],
            ['id' => 4, 'role_name' => 'Marketing'],
            ['id' => 5, 'role_name' => 'Teller'],
            ['id' => 6, 'role_name' => 'Customer Service'],
            ['id' => 7, 'role_name' => 'Analis Kredit'],
            ['id' => 8, 'role_name' => 'Debt Collector'],
            ['id' => 9, 'role_name' => 'Nasabah'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                array_merge($role, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
