<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */ 
    public function run(): void
    {
        $this->call([
            // Master data seeders
            RoleSeeder::class,
            UnitSeeder::class,
            LoanProductSeeder::class,
            DepositProductSeeder::class,
            SystemConfigurationSeeder::class,
            ExternalBankSeeder::class,
            FaqSeeder::class,
            AnnouncementSeeder::class,
            BillerProductSeeder::class,
            
            // Transactional data seeders
            UserSeeder::class,
            CustomerProfileSeeder::class,
            AccountSeeder::class,
            CardSeeder::class,
            WithdrawalAccountSeeder::class,
            LoanSeeder::class,
            LoanInstallmentSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
