<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standing_instructions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('from_account_id')->nullable();
            $table->string('to_account_number', 20);
            $table->string('to_bank_code', 10)->nullable();
            $table->decimal('amount', 20, 2);
            $table->text('description')->nullable();
            $table->enum('frequency', ['monthly', 'weekly']);
            $table->integer('execution_day');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'paused', 'ended'])->default('active');
            $table->date('last_executed')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standing_instructions');
    }
};
