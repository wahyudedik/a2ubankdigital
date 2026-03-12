<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biller_products', function (Blueprint $table) {
            $table->id();
            $table->string('buyer_sku_code', 100);
            $table->string('product_name', 255);
            $table->string('category', 100);
            $table->string('brand', 100);
            $table->enum('type', ['prepaid', 'postpaid']);
            $table->integer('price');
            $table->text('desc')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biller_products');
    }
};
