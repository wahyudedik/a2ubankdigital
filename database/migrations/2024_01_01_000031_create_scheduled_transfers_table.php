<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('from_account_id')->nullable();
            $table->string('to_account_number', 20);
            $table->decimal('amount', 20, 2);
            $table->text('description')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->enum('status', ['pending', 'executed', 'failed'])->default('pending');
            $table->timestamp('executed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('transaction_id')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_transfers');
    }
};
