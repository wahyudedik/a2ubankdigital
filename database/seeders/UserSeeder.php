<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder 
{
    public function run(): void
    {
        // Create users using Laravel User model (bawaan Laravel)
        $users = [
            [
                'id' => 78,
                'bank_id' => 'NIP-1212230000421',
                'role_id' => 1, // Super Admin
                'full_name' => 'Super Administrator',
                'email' => 'admin@a2ubank.com',
                'phone_number' => '089676000378', 
                'password_hash' => bcrypt('admin123'),
                'pin_hash' => null,
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 79,
                'bank_id' => 'NIP-202602-973726',
                'role_id' => 5, // Teller
                'full_name' => 'Novita Anisa',
                'email' => 'teller@a2ubank.com',
                'phone_number' => '081234567890',
                'password_hash' => bcrypt('teller123'),
                'pin_hash' => null,
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 80,
                'bank_id' => 'NIP-202603-100002',
                'role_id' => 2, // Kepala Cabang
                'full_name' => 'Budi Santoso',
                'email' => 'kacab@a2ubank.com',
                'phone_number' => '081200000002',
                'password_hash' => bcrypt('kacab123'),
                'pin_hash' => null,
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 81,
                'bank_id' => 'NIP-202603-100003',
                'role_id' => 3, // Kepala Unit
                'full_name' => 'Siti Rahayu',
                'email' => 'kaunit@a2ubank.com',
                'phone_number' => '081200000003',
                'password_hash' => bcrypt('kaunit123'),
                'pin_hash' => null,
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 82,
                'bank_id' => 'NIP-202603-100004',
                'role_id' => 4, // Marketing
                'full_name' => 'Dian Permata',
                'email' => 'marketing@a2ubank.com',
                'phone_number' => '081200000004',
                'password_hash' => bcrypt('marketing123'),
                'pin_hash' => null,
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 83,
                'bank_id' => 'NIP-202603-100006',
                'role_id' => 6, // Customer Service
                'full_name' => 'Rina Wulandari',
                'email' => 'cs@a2ubank.com',
                'phone_number' => '081200000006',
                'password_hash' => bcrypt('cs123'),
                'pin_hash' => null,
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 84,
                'bank_id' => 'NIP-202603-100007',
                'role_id' => 7, // Analis Kredit
                'full_name' => 'Hendra Wijaya',
                'email' => 'analis@a2ubank.com',
                'phone_number' => '081200000007',
                'password_hash' => bcrypt('analis123'),
                'pin_hash' => null,
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 76,
                'bank_id' => 'NIP-202603-100008',
                'role_id' => 8, // Debt Collector
                'full_name' => 'Agus Firmansyah',
                'email' => 'collector@a2ubank.com',
                'phone_number' => '081200000008',
                'password_hash' => bcrypt('collector123'),
                'pin_hash' => null,
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 85,
                'bank_id' => 'CIF-202602-884523',
                'role_id' => 9, // Customer
                'full_name' => 'Andre Aldi Utama',
                'email' => 'customer1@example.com',
                'phone_number' => '089676000377',
                'password_hash' => bcrypt('customer123'),
                'pin_hash' => bcrypt('123456'),
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 89,
                'bank_id' => 'CIF-202602-509474',
                'role_id' => 9, // Customer
                'full_name' => 'Chandra Budi Setiawan',
                'email' => 'customer2@example.com',
                'phone_number' => '081311819060',
                'password_hash' => bcrypt('customer123'),
                'pin_hash' => bcrypt('654321'),
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 90,
                'bank_id' => 'CIF-202602-139088',
                'role_id' => 9, // Customer
                'full_name' => 'Sahri Mandala',
                'email' => 'customer3@example.com',
                'phone_number' => '082180314939',
                'password_hash' => bcrypt('customer123'),
                'pin_hash' => bcrypt('111111'),
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
            [
                'id' => 93,
                'bank_id' => 'CIF-202603-214567',
                'role_id' => 9, // Customer
                'full_name' => 'Rizky Pratama',
                'email' => 'customer4@example.com',
                'phone_number' => '081234567891',
                'password_hash' => bcrypt('customer123'),
                'pin_hash' => bcrypt('222222'),
                'status' => 'ACTIVE',
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
                'is_2fa_enabled' => false,
                'two_factor_secret' => null,
            ],
        ];

        // Insert menggunakan DB karena struktur database berbeda dengan User model
        foreach ($users as $userData) {
            DB::table('users')->insert(array_merge($userData, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
