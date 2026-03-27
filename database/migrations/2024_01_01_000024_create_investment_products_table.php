<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name', 255);
            $table->string('product_code', 20);
            $table->enum('category', ['reksadana', 'saham', 'obligasi']);
            $table->text('description')->nullable();
            $table->enum('risk_profile', ['rendah', 'menengah', 'tinggi']);
            $table->decimal('minimum_investment', 20, 2)->default(100000.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_products');
    }
};
