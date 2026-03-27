<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id')->nullable();
            $table->integer('installment_number');
            $table->date('due_date');
            $table->decimal('principal_amount', 20, 2);
            $table->decimal('interest_amount', 20, 2);
            $table->decimal('total_amount', 20, 2);
            $table->decimal('paid_amount', 20, 2)->default(0);
            $table->enum('status', ['PENDING', 'PAID', 'OVERDUE', 'PARTIAL'])->default('PENDING');
            $table->timestamp('paid_at')->nullable();
            $table->decimal('late_fee', 20, 2)->default(0);
            $table->timestamps();

            $table->index('loan_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};
