<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest_accruals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->decimal('amount', 20, 4);
            $table->date('calculation_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_accruals');
    }
};
