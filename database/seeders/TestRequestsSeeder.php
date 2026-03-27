<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestRequestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some user IDs for testing
        $userIds = DB::table('users')->where('role_id', '!=', 1)->pluck('id')->take(3);
        $adminId = DB::table('users')->where('role_id', 1)->first()->id ?? null;
        
        if ($userIds->isEmpty()) {
            $this->command->info('No users found for seeding requests');
            return;
        }

        if (!$adminId) {
            $this->command->info('No admin user found for seeding requests');
            return;
        }

        // Create some topup requests
        $topupRequests = [];
        foreach ($userIds as $userId) {
            $topupRequests[] = [
                'user_id' => $userId,
                'amount' => rand(100000, 1000000),
                'payment_method' => ['Bank Transfer', 'E-Wallet', 'Cash Deposit'][rand(0, 2)],
                'proof_of_payment_url' => 'uploads/proof_' . rand(1000, 9999) . '.jpg',
                'status' => ['pending', 'approved', 'rejected'][rand(0, 2)],
                'processed_by' => rand(0, 1) ? $adminId : null,
                'processed_at' => rand(0, 1) ? Carbon::now()->subDays(rand(1, 7)) : null,
                'rejection_reason' => rand(0, 1) ? 'Invalid proof of payment' : null,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(0, 5))
            ];
        }

        DB::table('topup_requests')->insert($topupRequests);

        // Create some withdrawal accounts first
        $withdrawalAccounts = [];
        foreach ($userIds as $userId) {
            $withdrawalAccounts[] = [
                'user_id' => $userId,
                'bank_name' => ['BCA', 'Mandiri', 'BNI', 'BRI'][rand(0, 3)],
                'account_number' => '1234567890' . rand(10, 99),
                'account_name' => 'Test Account ' . $userId,
                'created_at' => Carbon::now()->subDays(rand(30, 60)),
                'updated_at' => Carbon::now()->subDays(rand(0, 5))
            ];
        }

        DB::table('withdrawal_accounts')->insert($withdrawalAccounts);

        // Get the created withdrawal account IDs
        $withdrawalAccountIds = DB::table('withdrawal_accounts')
            ->whereIn('user_id', $userIds)
            ->pluck('id');

        // Create some withdrawal requests
        $withdrawalRequests = [];
        foreach ($withdrawalAccountIds as $index => $accountId) {
            $userId = $userIds[$index % count($userIds)];
            $withdrawalRequests[] = [
                'user_id' => $userId,
                'withdrawal_account_id' => $accountId,
                'amount' => rand(50000, 500000),
                'status' => ['pending', 'approved', 'rejected', 'completed'][rand(0, 3)],
                'processed_by' => rand(0, 1) ? $adminId : null,
                'processed_at' => rand(0, 1) ? Carbon::now()->subDays(rand(1, 7)) : null,
                'rejection_reason' => rand(0, 1) ? 'Insufficient documentation' : null,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(0, 5))
            ];
        }

        DB::table('withdrawal_requests')->insert($withdrawalRequests);

        // Create some card requests
        $cardRequests = [];
        foreach ($userIds as $userId) {
            $cardRequests[] = [
                'user_id' => $userId,
                'card_type' => ['DEBIT', 'CREDIT'][rand(0, 1)],
                'delivery_address' => 'Test Address ' . $userId . ', Jakarta',
                'reason' => 'Need new card for daily transactions',
                'status' => ['PENDING', 'APPROVED', 'REJECTED'][rand(0, 2)],
                'processed_by' => rand(0, 1) ? $adminId : null,
                'processed_at' => rand(0, 1) ? Carbon::now()->subDays(rand(1, 7)) : null,
                'rejection_reason' => rand(0, 1) ? 'Incomplete documentation' : null,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(0, 5))
            ];
        }

        DB::table('card_requests')->insert($cardRequests);

        $this->command->info('Test requests seeded successfully!');
    }
}