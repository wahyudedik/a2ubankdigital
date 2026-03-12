<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FullDataSeeder extends Seeder
{
    /**
     * Seed all data from database.sql
     * This seeder imports data directly using raw SQL for efficiency
     */
    public function run(): void
    {
        $sqlFile = base_path('../database.sql');
        
        if (!file_exists($sqlFile)) {
            $this->command->error('database.sql not found!');
            return;
        }
        
        $this->command->info('Reading database.sql...');
        $sql = file_get_contents($sqlFile);
        
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Extract and execute only INSERT statements
        preg_match_all('/INSERT INTO `(\w+)`[^;]+;/s', $sql, $matches, PREG_SET_ORDER);
        
        $this->command->info('Found ' . count($matches) . ' INSERT statements');
        
        $tableMapping = [
            'accounts' => true,
            'audit_logs' => true,
            'cards' => true,
            'customer_profiles' => true,
            'deposit_products' => true,
            'loans' => true,
            'loan_installments' => true,
            'loan_products' => true,
            'loyalty_points_history' => true,
            'notifications' => true,
            'password_resets' => true,
            'push_subscriptions' => true,
            'roles' => true,
            'system_configurations' => true,
            'system_logs' => true,
            'transactions' => true,
            'units' => true,
            'users' => true,
            'withdrawal_accounts' => true,
            'withdrawal_requests' => true,
        ];
        
        foreach ($matches as $match) {
            $tableName = $match[1];
            $insertStatement = $match[0];
            
            // Only process tables we want to seed
            if (!isset($tableMapping[$tableName])) {
                continue;
            }
            
            try {
                // Adjust INSERT statement to match Laravel migration structure
                $adjustedStatement = $this->adjustInsertStatement($tableName, $insertStatement);
                
                if ($adjustedStatement) {
                    DB::statement($adjustedStatement);
                    $this->command->info("✓ Seeded: $tableName");
                }
            } catch (\Exception $e) {
                $this->command->warn("✗ Failed: $tableName - " . $e->getMessage());
            }
        }
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('Data seeding completed!');
    }
    
    /**
     * Adjust INSERT statement to match Laravel migration structure
     */
    private function adjustInsertStatement(string $table, string $statement): ?string
    {
        // Map of columns to remove/adjust per table
        $columnAdjustments = [
            'users' => [
                'remove' => ['unit_id', 'loyalty_points_balance', 'notification_prefs', 'two_factor_secret', 'profile_picture_url', 'ktp_image_path', 'selfie_image_path'],
                'rename' => ['password_hash' => 'password_hash']
            ],
            'customer_profiles' => [
                'remove' => ['registered_by', 'registration_method', 'verified_by'],
                'rename' => ['kyc_status' => 'kyc_status'] // APPROVED -> VERIFIED
            ],
            'loans' => [
                'remove' => ['account_id', 'application_date', 'approval_date', 'disbursement_date'],
                'add' => ['monthly_installment', 'total_interest', 'total_repayment', 'approved_at', 'disbursed_at', 'first_payment_date']
            ],
            'transactions' => [
                'remove' => ['processed_by', 'external_ref_id', 'external_sn'],
                'rename' => [
                    'TARIK_TUNAI' => 'WITHDRAWAL',
                    'PENCAIRAN_PINJAMAN' => 'LOAN_DISBURSEMENT',
                    'BAYAR_CICILAN' => 'LOAN_PAYMENT',
                    'PEMBUKAAN_DEPOSITO' => 'DEPOSIT'
                ]
            ],
        ];
        
        // For now, return original statement
        // TODO: Implement column adjustments if needed
        return $statement;
    }
}
