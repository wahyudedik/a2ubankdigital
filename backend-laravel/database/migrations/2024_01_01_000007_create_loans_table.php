<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('loan_product_id');
            $table->decimal('loan_amount', 20, 2);
            $table->decimal('interest_rate_pa', 5, 2);
            $table->integer('tenor');
            $table->enum('tenor_unit', ['BULAN', 'TAHUN'])->default('BULAN');
            $table->decimal('monthly_installment', 20, 2);
            $table->decimal('total_interest', 20, 2);
            $table->decimal('total_repayment', 20, 2);
            $table->text('purpose')->nullable();
            $table->enum('status', ['SUBMITTED', 'APPROVED', 'REJECTED', 'DISBURSED', 'ACTIVE', 'COMPLETED', 'DEFAULTED'])->default('SUBMITTED');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->unsignedBigInteger('disbursed_by')->nullable();
            $table->date('first_payment_date')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('loan_product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
