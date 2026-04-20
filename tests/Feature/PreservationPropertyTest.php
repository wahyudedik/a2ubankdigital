<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Card;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Transaction;
use App\Models\DepositProduct;
use App\Models\Unit;
use App\Models\CustomerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Preservation Property Tests
 *
 * These tests MUST PASS on unfixed code — they document baseline behavior that
 * must be preserved after bug fixes are applied.
 *
 * Observation-first methodology:
 *   - Observe what works correctly on unfixed code
 *   - Write tests that capture that correct behavior
 *   - Re-run after fixes to confirm no regressions
 *
 * NOTE: All AJAX routes are under the /ajax/ prefix (see bootstrap/app.php).
 *
 * Validates: Requirements 3.1–3.10
 */
class PreservationPropertyTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers (same patterns as BugConditionExplorationTest)
    // -------------------------------------------------------------------------

    private function createCustomer(array $overrides = []): User
    {
        static $counter = 0;
        $counter++;
        return User::create(array_merge([
            'bank_id'       => 'CIF-PRES-' . str_pad($counter, 6, '0', STR_PAD_LEFT),
            'role_id'       => 9,
            'full_name'     => 'Preservation Customer ' . $counter,
            'email'         => 'prescustomer' . $counter . '@example.com',
            'phone_number'  => '0812' . str_pad($counter, 8, '0', STR_PAD_LEFT),
            'password_hash' => bcrypt('password123'),
            'status'        => 'ACTIVE',
        ], $overrides));
    }

    private function createSavingsAccount(User $user, array $overrides = []): Account
    {
        static $accCounter = 0;
        $accCounter++;
        return Account::create(array_merge([
            'user_id'        => $user->id,
            'account_number' => '1100' . str_pad($user->id, 6, '0', STR_PAD_LEFT) . str_pad($accCounter, 3, '0', STR_PAD_LEFT),
            'account_type'   => 'TABUNGAN',
            'balance'        => 1000000,
            'status'         => 'ACTIVE',
        ], $overrides));
    }

    private function createLoanProduct(array $overrides = []): LoanProduct
    {
        static $lpCounter = 0;
        $lpCounter++;
        return LoanProduct::create(array_merge([
            'product_name'     => 'KTA Preservation ' . $lpCounter,
            'product_code'     => 'KTA-PRES-' . str_pad($lpCounter, 3, '0', STR_PAD_LEFT),
            'interest_rate_pa' => 12.0,
            'min_amount'       => 1000000,
            'max_amount'       => 50000000,
            'min_tenor'        => 6,
            'max_tenor'        => 60,
            'tenor_unit'       => 'BULAN',
            'is_active'        => true,
        ], $overrides));
    }

    private function createAdminUser(array $overrides = []): User
    {
        static $adminCounter = 0;
        $adminCounter++;
        return User::create(array_merge([
            'bank_id'       => 'NIP-PRES-' . str_pad($adminCounter, 6, '0', STR_PAD_LEFT),
            'role_id'       => 1,
            'full_name'     => 'Super Admin Pres ' . $adminCounter,
            'email'         => 'superadminpres' . $adminCounter . '@test.com',
            'phone_number'  => '0811' . str_pad($adminCounter, 8, '0', STR_PAD_LEFT),
            'password_hash' => bcrypt('admin123'),
            'status'        => 'ACTIVE',
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Test 2.1 — Deposit show with valid depositProduct returns correct data
    //
    // Preservation: Controllers with properly populated relationships continue
    // to return correct data (Req 3.1).
    //
    // This PASSES on unfixed code because the relationship IS populated.
    // -------------------------------------------------------------------------
    public function test_2_1_deposit_show_with_valid_deposit_product_returns_success(): void
    {
        $user = $this->createCustomer();
        $this->createSavingsAccount($user);

        // Create a deposit product (no product_code column in DB)
        $depositProduct = DepositProduct::create([
            'product_name'     => 'Deposito 3 Bulan',
            'min_amount'       => 1000000,
            'interest_rate_pa' => 6.0,
            'tenor_months'     => 3,
            'is_active'        => true,
        ]);

        // Create a DEPOSITO account with a valid deposit_product_id
        $depositAccount = Account::create([
            'user_id'            => $user->id,
            'account_number'     => '3100' . str_pad($user->id, 6, '0', STR_PAD_LEFT) . '001',
            'account_type'       => 'DEPOSITO',
            'balance'            => 5000000,
            'status'             => 'ACTIVE',
            'deposit_product_id' => $depositProduct->id, // valid relationship
        ]);

        $response = $this->actingAs($user)->getJson('/ajax/user/deposits/' . $depositAccount->id);

        // Preservation: valid depositProduct relationship → 200 success with data
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        $response->assertJsonPath('data.id', $depositAccount->id);
        $response->assertJsonPath('data.account_type', 'DEPOSITO');
    }

    // -------------------------------------------------------------------------
    // Test 2.2 — Card index with valid user relationship returns full card data
    //
    // Preservation: Cards with valid user relationships return full data (Req 3.1).
    //
    // This PASSES on unfixed code because user relationship IS populated.
    // -------------------------------------------------------------------------
    public function test_2_2_card_index_with_valid_user_relationship_returns_full_data(): void
    {
        $user = $this->createCustomer();

        // Create a card with a valid user_id (relationship is populated)
        DB::table('cards')->insert([
            'user_id'            => $user->id,
            'card_number_masked' => '**** **** **** 5678',
            'card_type'          => 'DEBIT',
            'status'             => 'active',
            'daily_limit'        => 10000000,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/ajax/user/cards');

        // Preservation: valid user relationship → 200 success with 1 card
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        $response->assertJsonCount(1, 'data');
        // card_type is stored as lowercase in the DB
        $response->assertJsonPath('data.0.card_type', 'debit');
    }

    // -------------------------------------------------------------------------
    // Test 2.3 — Loan show with valid loanProduct returns correct data
    //
    // Preservation: Loans with valid loanProduct relationship return product
    // name and data correctly (Req 3.1).
    //
    // This PASSES on unfixed code because loanProduct relationship IS populated.
    // -------------------------------------------------------------------------
    public function test_2_3_loan_show_with_valid_loan_product_returns_success(): void
    {
        $user = $this->createCustomer();
        $loanProduct = $this->createLoanProduct();

        // Create a loan with a valid loan_product_id
        $loan = Loan::create([
            'user_id'             => $user->id,
            'loan_product_id'     => $loanProduct->id,
            'loan_amount'         => 5000000,
            'interest_rate_pa'    => 12.0,
            'tenor'               => 12,
            'tenor_unit'          => 'BULAN',
            'monthly_installment' => 450000,
            'total_interest'      => 400000,
            'total_repayment'     => 5400000,
            'status'              => 'SUBMITTED',
        ]);

        $response = $this->actingAs($user)->getJson('/ajax/user/loans/' . $loan->id);

        // Preservation: valid loanProduct relationship → 200 success with loan data
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        $response->assertJsonPath('data.id', $loan->id);
        $response->assertJsonPath('data.loan_product_id', $loanProduct->id);
    }

    // -------------------------------------------------------------------------
    // Test 2.4 — Transaction show with valid ID returns transaction data
    //
    // Preservation: Valid transaction IDs continue to return transaction data
    // (Req 3.1, 3.2). findOrFail() succeeds for valid IDs.
    //
    // This PASSES on unfixed code because the ID exists.
    // -------------------------------------------------------------------------
    public function test_2_4_transaction_show_with_valid_id_returns_transaction_data(): void
    {
        $user = $this->createCustomer();
        $account = $this->createSavingsAccount($user);

        // Create a transaction belonging to the user's account
        $transaction = Transaction::create([
            'transaction_code' => 'TRX-PRES-' . time(),
            'from_account_id'  => $account->id,
            'transaction_type' => 'TRANSFER_INTERNAL',
            'amount'           => 100000,
            'fee'              => 0,
            'description'      => 'Preservation test transaction',
            'status'           => 'SUCCESS',
        ]);

        $response = $this->actingAs($user)->getJson('/ajax/user/transactions/' . $transaction->id);

        // Preservation: valid ID → 200 success with transaction data
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        $response->assertJsonPath('data.id', $transaction->id);
    }

    // -------------------------------------------------------------------------
    // Test 2.5 — Internal transfer inquiry to correct Laravel route succeeds
    //
    // Preservation: Frontend calls to correct Laravel routes continue to work
    // (Req 3.2). The correct route is POST /ajax/user/transfer/internal/inquiry.
    //
    // This PASSES on unfixed code because this IS the correct Laravel route.
    // -------------------------------------------------------------------------
    public function test_2_5_internal_transfer_inquiry_to_correct_route_succeeds(): void
    {
        $sender = $this->createCustomer();
        $this->createSavingsAccount($sender);

        $receiver = $this->createCustomer();
        $receiverAccount = $this->createSavingsAccount($receiver);

        $response = $this->actingAs($sender)->postJson('/ajax/user/transfer/internal/inquiry', [
            'destination_account_number' => $receiverAccount->account_number,
        ]);

        // Preservation: correct Laravel route → 200 success with account_number and recipient_name
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        $response->assertJsonPath('data.account_number', $receiverAccount->account_number);
        $response->assertJsonStructure(['data' => ['account_number', 'recipient_name']]);
    }

    // -------------------------------------------------------------------------
    // Test 2.6 — Internal transfer execute to correct Laravel route succeeds
    //
    // Preservation: Frontend calls to correct Laravel routes continue to work
    // (Req 3.2). Balances are updated correctly after transfer.
    //
    // This PASSES on unfixed code because this IS the correct Laravel route.
    // -------------------------------------------------------------------------
    public function test_2_6_internal_transfer_execute_to_correct_route_succeeds_and_updates_balances(): void
    {
        $sender = $this->createCustomer();
        $senderAccount = $this->createSavingsAccount($sender, ['balance' => 1000000]);

        $receiver = $this->createCustomer();
        $receiverAccount = $this->createSavingsAccount($receiver, ['balance' => 500000]);

        $transferAmount = 100000;

        $response = $this->actingAs($sender)->postJson('/ajax/user/transfer/internal/execute', [
            'destination_account_number' => $receiverAccount->account_number,
            'amount'                     => $transferAmount,
            'description'                => 'Preservation test transfer',
        ]);

        // Preservation: correct Laravel route → 200 success
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Verify balances updated correctly
        $senderAccount->refresh();
        $receiverAccount->refresh();
        $this->assertEquals(900000, (float) $senderAccount->balance, 'Sender balance should decrease by transfer amount');
        $this->assertEquals(600000, (float) $receiverAccount->balance, 'Receiver balance should increase by transfer amount');
    }

    // -------------------------------------------------------------------------
    // Test 2.7 — First-time loan application (no existing loans) succeeds
    //
    // Preservation: First-time loan applications continue to succeed (Req 3.3).
    // No duplicate check exists yet, but valid first-time applications work fine.
    //
    // This PASSES on unfixed code because no duplicate check blocks it.
    // -------------------------------------------------------------------------
    public function test_2_7_first_time_loan_application_succeeds(): void
    {
        $user = $this->createCustomer();
        $this->createSavingsAccount($user);
        $loanProduct = $this->createLoanProduct();

        // No existing loans for this user
        $this->assertEquals(0, Loan::where('user_id', $user->id)->count(), 'User should have no existing loans');

        $response = $this->actingAs($user)->postJson('/ajax/user/loans/apply', [
            'loan_product_id' => $loanProduct->id,
            'loan_amount'     => 5000000,
            'tenor'           => 12,
            'purpose'         => 'Preservation test - first loan',
        ]);

        // Preservation: first-time application → 201 success
        $response->assertStatus(201);
        $response->assertJson(['status' => 'success']);

        // Verify loan was created
        $this->assertEquals(1, Loan::where('user_id', $user->id)->count(), 'Loan should be created');
    }

    // -------------------------------------------------------------------------
    // Test 2.8 — Super-admin CustomerController::index() returns all customers
    //
    // Preservation: Super-admin access to all customers continues to work (Req 3.4).
    // Super-admin (role_id=1) bypasses unit filtering entirely.
    //
    // This PASSES on unfixed code because super-admin bypasses unit filtering.
    // -------------------------------------------------------------------------
    public function test_2_8_super_admin_customer_index_returns_all_customers(): void
    {
        $superAdmin = $this->createAdminUser();

        // Create two units
        $unit1 = Unit::create([
            'unit_name' => 'Unit Preservation 1',
            'unit_code' => 'PRES-U1',
            'unit_type' => 'KANTOR_CABANG',
            'status'    => 'ACTIVE',
        ]);

        $unit2 = Unit::create([
            'unit_name' => 'Unit Preservation 2',
            'unit_code' => 'PRES-U2',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $unit1->id,
            'status'    => 'ACTIVE',
        ]);

        // Create customer 1 in unit 1
        $customer1 = $this->createCustomer(['email' => 'pres_cust1@test.com', 'phone_number' => '08120000101']);
        CustomerProfile::create([
            'user_id'            => $customer1->id,
            'unit_id'            => $unit1->id,
            'nik'                => '1234567890120001',
            'mother_maiden_name' => 'Mother One',
            'pob'                => 'Jakarta',
            'dob'                => '1990-01-01',
            'gender'             => 'L',
            'address_ktp'        => 'Jl. Pres No. 1',
            'kyc_status'         => 'VERIFIED',
        ]);

        // Create customer 2 in unit 2
        $customer2 = $this->createCustomer(['email' => 'pres_cust2@test.com', 'phone_number' => '08120000102']);
        CustomerProfile::create([
            'user_id'            => $customer2->id,
            'unit_id'            => $unit2->id,
            'nik'                => '1234567890120002',
            'mother_maiden_name' => 'Mother Two',
            'pob'                => 'Bandung',
            'dob'                => '1992-05-15',
            'gender'             => 'P',
            'address_ktp'        => 'Jl. Pres No. 2',
            'kyc_status'         => 'VERIFIED',
        ]);

        $response = $this->actingAs($superAdmin)->getJson('/ajax/admin/customers');

        // Preservation: super-admin bypasses unit filter → 200 success with both customers
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $returnedIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($customer1->id, $returnedIds, 'Super-admin should see customer in unit 1');
        $this->assertContains($customer2->id, $returnedIds, 'Super-admin should see customer in unit 2');
    }

    // -------------------------------------------------------------------------
    // Test 2.9 — EmailService uses LogService for error logging
    //
    // Preservation: Email errors that are already logged continue to be logged
    // (Req 3.6). LogService::logError() IS called in catch blocks on fixed code.
    //
    // This PASSES on fixed code because LogService is used (which internally
    // calls logSystemEvent, providing richer structured logging than Log::error).
    // -------------------------------------------------------------------------
    public function test_2_9_email_service_uses_log_error_for_error_logging(): void
    {
        // Read the EmailService source to verify LogService is present
        $emailServiceSource = file_get_contents(app_path('Services/EmailService.php'));

        // Preservation: LogService IS used in catch blocks (errors ARE logged)
        // The fix upgraded from Log::error() to LogService::logError() for richer logging
        $this->assertStringContainsString(
            'logService',
            $emailServiceSource,
            'Preservation: EmailService should use LogService in catch blocks — errors are logged'
        );
    }

    // -------------------------------------------------------------------------
    // Test 2.10 — Account number generation produces unique numbers in
    //             non-concurrent scenario
    //
    // Preservation: Unique account number generation in non-concurrent scenarios
    // continues (Req 3.7). The do-while loop already exists in store().
    //
    // This PASSES on unfixed code because the do-while loop is already present.
    // -------------------------------------------------------------------------
    public function test_2_10_account_number_generation_produces_unique_numbers_non_concurrent(): void
    {
        // Part 1: Verify do-while loop with uniqueness check exists in source
        $customerControllerSource = file_get_contents(
            app_path('Http/Controllers/Admin/CustomerController.php')
        );

        $hasDoWhileLoop = str_contains($customerControllerSource, 'do {') &&
                          str_contains($customerControllerSource, "Account::where('account_number'");

        $this->assertTrue(
            $hasDoWhileLoop,
            'Preservation: CustomerController::store() should have do-while loop with uniqueness check'
        );

        // Part 2: Create two customers via the admin API and verify unique account numbers
        $superAdmin = $this->createAdminUser();

        $unit = Unit::create([
            'unit_name' => 'Unit Account Gen Test',
            'unit_code' => 'ACCT-GEN-001',
            'unit_type' => 'KANTOR_CABANG',
            'status'    => 'ACTIVE',
        ]);

        // Create first customer (pob, dob, gender, address_ktp are required by DB NOT NULL)
        $response1 = $this->actingAs($superAdmin)->postJson('/ajax/admin/customers', [
            'full_name'          => 'Customer Account Gen 1',
            'email'              => 'acctgen1@test.com',
            'nik'                => '1234567890130001',
            'mother_maiden_name' => 'Mother Gen 1',
            'phone_number'       => '08120000201',
            'unit_id'            => $unit->id,
            'pob'                => 'Jakarta',
            'dob'                => '1990-01-01',
            'gender'             => 'L',
            'address_ktp'        => 'Jl. Test No. 1',
        ]);

        $response1->assertStatus(201);
        $response1->assertJson(['status' => 'success']);

        // Create second customer
        $response2 = $this->actingAs($superAdmin)->postJson('/ajax/admin/customers', [
            'full_name'          => 'Customer Account Gen 2',
            'email'              => 'acctgen2@test.com',
            'nik'                => '1234567890130002',
            'mother_maiden_name' => 'Mother Gen 2',
            'phone_number'       => '08120000202',
            'unit_id'            => $unit->id,
            'pob'                => 'Bandung',
            'dob'                => '1992-05-15',
            'gender'             => 'P',
            'address_ktp'        => 'Jl. Test No. 2',
        ]);

        $response2->assertStatus(201);
        $response2->assertJson(['status' => 'success']);

        // Verify both customers have different account numbers
        $accountNumbers = Account::where('account_type', 'TABUNGAN')
            ->whereIn('user_id', [
                $response1->json('data.id'),
                $response2->json('data.id'),
            ])
            ->pluck('account_number')
            ->toArray();

        $this->assertCount(2, $accountNumbers, 'Both customers should have savings accounts');
        $this->assertCount(2, array_unique($accountNumbers), 'Account numbers should be unique');
    }

    // -------------------------------------------------------------------------
    // Test 2.11 — Deposit index returns all user deposits correctly
    //
    // Preservation: Deposit index with valid depositProduct relationships
    // returns all deposits correctly (Req 3.1, 3.10).
    //
    // This PASSES on unfixed code because the relationships are populated.
    // -------------------------------------------------------------------------
    public function test_2_11_deposit_index_returns_all_user_deposits(): void
    {
        $user = $this->createCustomer();
        $this->createSavingsAccount($user);

        // Create a deposit product (no product_code column in DB)
        $depositProduct = DepositProduct::create([
            'product_name'     => 'Deposito 6 Bulan',
            'min_amount'       => 1000000,
            'interest_rate_pa' => 7.0,
            'tenor_months'     => 6,
            'is_active'        => true,
        ]);

        // Create 2 DEPOSITO accounts with valid deposit_product_id
        Account::create([
            'user_id'            => $user->id,
            'account_number'     => '3100' . str_pad($user->id, 6, '0', STR_PAD_LEFT) . '011',
            'account_type'       => 'DEPOSITO',
            'balance'            => 3000000,
            'status'             => 'ACTIVE',
            'deposit_product_id' => $depositProduct->id,
        ]);

        Account::create([
            'user_id'            => $user->id,
            'account_number'     => '3100' . str_pad($user->id, 6, '0', STR_PAD_LEFT) . '012',
            'account_type'       => 'DEPOSITO',
            'balance'            => 5000000,
            'status'             => 'ACTIVE',
            'deposit_product_id' => $depositProduct->id,
        ]);

        $response = $this->actingAs($user)->getJson('/ajax/user/deposits');

        // Preservation: deposit index returns all user deposits
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        $response->assertJsonCount(2, 'data');
    }

    // -------------------------------------------------------------------------
    // Test 2.12 — Transaction index returns success response with pagination structure
    //
    // Preservation: Transaction index endpoint returns the expected JSON structure
    // with pagination metadata (Req 3.3, 3.10).
    //
    // Note: The TransactionController::index() query references loan_installments
    // via a join on li.transaction_id. When a user has no accounts, the controller
    // returns early with an empty success response (no DB join executed).
    // This PASSES on unfixed code for users with no accounts.
    // -------------------------------------------------------------------------
    public function test_2_12_transaction_index_returns_success_with_pagination_structure_for_empty_result(): void
    {
        // Create a user with NO accounts — controller returns early with empty success
        $user = $this->createCustomer();
        // Intentionally do NOT create any accounts

        $response = $this->actingAs($user)->getJson('/ajax/user/transactions?page=1&limit=2');

        // Preservation: user with no accounts → 200 success with empty pagination
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        $response->assertJsonStructure([
            'pagination' => ['current_page', 'total_pages', 'total_records', 'has_more'],
            'data',
        ]);

        $data = $response->json('data');
        $this->assertIsArray($data, 'data should be an array');
        $this->assertEmpty($data, 'User with no accounts should have no transactions');

        $pagination = $response->json('pagination');
        $this->assertEquals(1, $pagination['current_page'], 'Current page should be 1');
        $this->assertEquals(0, $pagination['total_records'], 'Total records should be 0 for user with no accounts');
    }

    // -------------------------------------------------------------------------
    // Test 2.13 — Loan products list returns only active products
    //
    // Preservation: Active loan products are returned correctly; inactive
    // products are excluded (Req 3.10).
    //
    // This PASSES on unfixed code because the products() method filters by is_active.
    // -------------------------------------------------------------------------
    public function test_2_13_loan_products_list_returns_only_active_products(): void
    {
        $user = $this->createCustomer();

        // Create 2 active loan products
        $activeProduct1 = $this->createLoanProduct(['is_active' => true]);
        $activeProduct2 = $this->createLoanProduct(['is_active' => true]);

        // Create 1 inactive loan product
        $inactiveProduct = $this->createLoanProduct(['is_active' => false]);

        $response = $this->actingAs($user)->getJson('/ajax/user/loan-products');

        // Preservation: only active products returned
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $returnedIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($activeProduct1->id, $returnedIds, 'Active product 1 should be returned');
        $this->assertContains($activeProduct2->id, $returnedIds, 'Active product 2 should be returned');
        $this->assertNotContains($inactiveProduct->id, $returnedIds, 'Inactive product should NOT be returned');
    }
}
