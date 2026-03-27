<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('card_number_masked', 20);
            $table->enum('card_type', ['debit', 'credit'])->default('debit');
            $table->enum('status', ['requested', 'active', 'blocked', 'expired'])->default('requested');
            $table->decimal('daily_limit', 20, 2)->nullable()->default(5000000.00);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
