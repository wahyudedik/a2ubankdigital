<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            // Nomor kartu penuh yang dienkripsi — nullable agar backward compatible
            $table->text('card_number_encrypted')->nullable()->after('card_number_masked');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('card_number_encrypted');
        });
    }
};
