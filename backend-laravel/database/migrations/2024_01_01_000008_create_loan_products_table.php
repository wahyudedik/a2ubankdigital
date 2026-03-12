<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name', 100);
            $table->string('product_code', 20)->unique();
            $table->decimal('min_amount', 20, 2);
            $table->decimal('max_amount', 20, 2);
            $table->decimal('interest_rate_pa', 5, 2);
            $table->integer('min_tenor');
            $table->integer('max_tenor');
            $table->enum('tenor_unit', ['BULAN', 'TAHUN'])->default('BULAN');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};
