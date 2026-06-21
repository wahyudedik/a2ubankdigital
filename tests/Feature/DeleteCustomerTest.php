<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteCustomerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'bank_id'       => 'NIP-1212230000421',
            'role_id'       => 1, // Super Admin
            'full_name'     => 'Super Administrator',
            'email'         => 'admin@a2ubank.com',
            'phone_number'  => '089676000378',
            'password_hash' => bcrypt('password'),
            'status'        => 'ACTIVE',
        ]);
    }

    private function createCustomer(): User
    {
        return User::create([
            'bank_id'       => 'CIF-TEST-123456',
            'role_id'       => 9, // Customer
            'full_name'     => 'Test Customer',
            'email'         => 'customer@example.com',
            'phone_number'  => '081234567890',
            'password_hash' => bcrypt('password'),
            'status'        => 'ACTIVE',
        ]);
    }

    public function test_admin_can_delete_customer_without_loans_or_balance(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();

        // Create an account with 0 balance
        Account::create([
            'user_id' => $customer->id,
            'account_number' => '1100000001',
            'account_type' => 'TABUNGAN',
            'balance' => 0,
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($admin, 'web')->delete("/admin/customers/{$customer->id}");

        $response->assertRedirect('/admin/customers');
        $response->assertSessionHas('success', 'Nasabah berhasil dihapus.');

        $this->assertSoftDeleted('users', [
            'id' => $customer->id,
        ]);

        // Check if account status is closed
        $this->assertDatabaseHas('accounts', [
            'user_id' => $customer->id,
            'status' => 'CLOSED',
        ]);
    }

    public function test_admin_cannot_delete_customer_with_outstanding_loans(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();

        // Create a loan with active/disbursed status
        Loan::create([
            'user_id' => $customer->id,
            'loan_product_id' => 1,
            'loan_amount' => 5000000,
            'interest_rate_pa' => 12.00,
            'tenor' => 12,
            'tenor_unit' => 'BULAN',
            'monthly_installment' => 450000,
            'total_interest' => 400000,
            'total_repayment' => 5400000,
            'purpose' => 'Business',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($admin, 'web')->delete("/admin/customers/{$customer->id}");

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_cannot_delete_customer_with_positive_balance(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();

        // Create an account with positive balance
        Account::create([
            'user_id' => $customer->id,
            'account_number' => '1100000001',
            'account_type' => 'TABUNGAN',
            'balance' => 150000,
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($admin, 'web')->delete("/admin/customers/{$customer->id}");

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'deleted_at' => null,
        ]);
    }
}
