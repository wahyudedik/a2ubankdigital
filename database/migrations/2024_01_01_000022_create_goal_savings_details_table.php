<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_savings_details', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('goal_name', 255);
            $table->decimal('goal_amount', 20, 2);
            $table->date('target_date')->nullable();
            $table->integer('autodebit_day');
            $table->decimal('autodebit_amount', 20, 2);
            $table->unsignedBigInteger('from_account_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_savings_details');
    }
};
