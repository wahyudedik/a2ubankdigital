<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('beneficiary_account_number', 20);
            $table->string('beneficiary_name', 255);
            $table->string('nickname', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
