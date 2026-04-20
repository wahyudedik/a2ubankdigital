<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: ubah ENUM untuk tambah nilai 'closed'
        DB::statement("ALTER TABLE `cards` MODIFY COLUMN `status` ENUM('requested','active','blocked','expired','closed') NOT NULL DEFAULT 'requested'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `cards` MODIFY COLUMN `status` ENUM('requested','active','blocked','expired') NOT NULL DEFAULT 'requested'");
    }
};
