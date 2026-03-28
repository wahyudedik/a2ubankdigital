<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 30)->unique();
            $table->unsignedBigInteger('from_account_id')->nullable();
            $table->unsignedBigInteger('to_account_id')->nullable();
            $table->enum('transaction_type', [
                'TRANSFER_INTERNAL',
                'TRANSFER_EXTERNAL',
                'TRANSFER_QR',
                'DEPOSIT',
                'WITHDRAWAL',
                'LOAN_DISBURSEMENT',
                'LOAN_PAYMENT',
                'BILL_PAYMENT',
                'DIGITAL_PRODUCT',
                'TOPUP',
                'TOPUP_EWALLET',
                'INTEREST_CREDIT',
                'FEE_DEBIT',
                'PEMBUKAAN_DEPOSITO',
                'PENCAIRAN_DEPOSITO',
                'REVERSED'
            ]);
            $table->decimal('amount', 20, 2);
            $table->decimal('fee', 20, 2)->default(0);
            $table->text('description')->nullable();
            $table->enum('status', ['PENDING', 'SUCCESS', 'FAILED', 'REVERSED'])->default('SUCCESS');
            $table->string('reference_number', 50)->nullable();
            $table->string('external_bank_code', 10)->nullable();
            $table->string('external_account_number', 30)->nullable();
            $table->string('external_account_name', 255)->nullable();
            $table->timestamps();

            $table->foreign('from_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('to_account_id')->references('id')->on('accounts')->onDelete('set null');
            
            $table->index('transaction_code');
            $table->index('from_account_id');
            $table->index('to_account_id');
            $table->index('transaction_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
