<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add MINGGU to loan_products tenor_unit enum
        DB::statement("ALTER TABLE `loan_products` MODIFY `tenor_unit` ENUM('MINGGU', 'BULAN', 'TAHUN') NOT NULL DEFAULT 'BULAN'");

        // Add MINGGU to loans tenor_unit enum
        DB::statement("ALTER TABLE `loans` MODIFY `tenor_unit` ENUM('MINGGU', 'BULAN', 'TAHUN') NOT NULL DEFAULT 'BULAN'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `loan_products` MODIFY `tenor_unit` ENUM('BULAN', 'TAHUN') NOT NULL DEFAULT 'BULAN'");
        DB::statement("ALTER TABLE `loans` MODIFY `tenor_unit` ENUM('BULAN', 'TAHUN') NOT NULL DEFAULT 'BULAN'");
    }
};
