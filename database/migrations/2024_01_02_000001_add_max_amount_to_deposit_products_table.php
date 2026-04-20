<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_products', function (Blueprint $table) {
            $table->decimal('max_amount', 20, 2)->nullable()->after('min_amount');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_products', function (Blueprint $table) {
            $table->dropColumn('max_amount');
        });
    }
};
