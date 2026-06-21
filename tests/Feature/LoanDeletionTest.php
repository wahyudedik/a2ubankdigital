<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'bank_id' => 'CIF-ADMIN-999',
            'role_id' => 1, // Super Admin
            'full_name' => 'Admin User',
            'email' => 'admin@test.com',
            'phone_number' => '0899999999',
            'password_hash' => bcrypt('password123'),
            'status' => 'ACTIVE'
        ]);
    }

    private function createCustomer(): User
    {
        return User::create([
            'bank_id' => 'CIF-CUST-999',
            'role_id' => 9, // Customer
            'full_name' => 'Customer User',
            'email' => 'customer@test.com',
            'phone_number' => '0811111111',
            'password_hash' => bcrypt('password123'),
            'status' => 'ACTIVE'
        ]);
    }

    private function createLoanProduct(): LoanProduct
    {
        return LoanProduct::create([
            'product_code' => 'LP-TEST',
            'product_name' => 'Pinjaman Test',
            'min_amount' => 100000,
            'max_amount' => 10000000,
            'interest_rate_pa' => 10,
            'min_tenor' => 1,
            'max_tenor' => 12,
            'tenor_unit' => 'BULAN',
            'late_payment_fee' => 3000,
            'is_active' => true
        ]);
    }

    private function createLoan(User $customer, LoanProduct $product, string $status): Loan
    {
        $loan = Loan::create([
            'user_id' => $customer->id,
            'loan_product_id' => $product->id,
            'loan_amount' => 500000,
            'interest_rate_pa' => 10,
            'tenor' => 4,
            'tenor_unit' => 'BULAN',
            'monthly_installment' => 130000,
            'total_interest' => 20000,
            'total_repayment' => 520000,
            'status' => $status
        ]);

        // Create 2 fake installments
        LoanInstallment::create([
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => now()->addMonth(),
            'principal_amount' => 125000,
            'interest_amount' => 5000,
            'total_amount' => 130000,
            'status' => 'PENDING'
        ]);

        LoanInstallment::create([
            'loan_id' => $loan->id,
            'installment_number' => 2,
            'due_date' => now()->addMonths(2),
            'principal_amount' => 125000,
            'interest_amount' => 5000,
            'total_amount' => 130000,
            'status' => 'PENDING'
        ]);

        return $loan;
    }

    public function test_admin_can_delete_completed_loan_and_its_installments(): void
    {
        $admin = $this->createAdmin();
        $cust = $this->createCustomer();
        $prod = $this->createLoanProduct();
        $loan = $this->createLoan($cust, $prod, 'COMPLETED');

        // Confirm database has installments
        $this->assertEquals(2, LoanInstallment::where('loan_id', $loan->id)->count());

        $response = $this->actingAs($admin)->deleteJson("/ajax/admin/loans/{$loan->id}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Verify loan and installments are deleted
        $this->assertDatabaseMissing('loans', ['id' => $loan->id]);
        $this->assertEquals(0, LoanInstallment::where('loan_id', $loan->id)->count());
    }

    public function test_admin_can_delete_rejected_loan_and_its_installments(): void
    {
        $admin = $this->createAdmin();
        $cust = $this->createCustomer();
        $prod = $this->createLoanProduct();
        $loan = $this->createLoan($cust, $prod, 'REJECTED');

        $response = $this->actingAs($admin)->deleteJson("/ajax/admin/loans/{$loan->id}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('loans', ['id' => $loan->id]);
        $this->assertEquals(0, LoanInstallment::where('loan_id', $loan->id)->count());
    }

    public function test_admin_cannot_delete_active_loan(): void
    {
        $admin = $this->createAdmin();
        $cust = $this->createCustomer();
        $prod = $this->createLoanProduct();
        $loan = $this->createLoan($cust, $prod, 'ACTIVE');

        $response = $this->actingAs($admin)->deleteJson("/ajax/admin/loans/{$loan->id}");

        $response->assertStatus(400);
        $response->assertJson(['status' => 'error']);

        // Verify loan still exists
        $this->assertDatabaseHas('loans', ['id' => $loan->id]);
        $this->assertEquals(2, LoanInstallment::where('loan_id', $loan->id)->count());
    }

    public function test_admin_cannot_delete_disbursed_loan(): void
    {
        $admin = $this->createAdmin();
        $cust = $this->createCustomer();
        $prod = $this->createLoanProduct();
        $loan = $this->createLoan($cust, $prod, 'DISBURSED');

        $response = $this->actingAs($admin)->deleteJson("/ajax/admin/loans/{$loan->id}");

        $response->assertStatus(400);
        $response->assertJson(['status' => 'error']);

        $this->assertDatabaseHas('loans', ['id' => $loan->id]);
    }

    public function test_admin_can_delete_disbursed_loan_if_fully_paid_off(): void
    {
        $admin = $this->createAdmin();
        $cust = $this->createCustomer();
        $prod = $this->createLoanProduct();
        $loan = $this->createLoan($cust, $prod, 'DISBURSED');

        // Mark installments as PAID
        LoanInstallment::where('loan_id', $loan->id)->update(['status' => 'PAID']);

        $response = $this->actingAs($admin)->deleteJson("/ajax/admin/loans/{$loan->id}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('loans', ['id' => $loan->id]);
        $this->assertEquals(0, LoanInstallment::where('loan_id', $loan->id)->count());
    }
}
