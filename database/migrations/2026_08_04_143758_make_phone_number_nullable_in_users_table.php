<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make phone_number nullable in users table.
     * Staff members may not have a phone number at creation time.
     * Existing data is preserved — this is a safe, non-destructive change.
     */
    public function up(): void
    {
        // Use raw SQL to change column to nullable without affecting the existing unique index
        DB::statement('ALTER TABLE `users` MODIFY COLUMN `phone_number` VARCHAR(20) NULL');
    }

    /**
     * Reverse the migration — restore NOT NULL constraint.
     */
    public function down(): void
    {
        // First, update any NULL phone_numbers to empty string to satisfy NOT NULL
        DB::table('users')->whereNull('phone_number')->update(['phone_number' => '']);

        DB::statement('ALTER TABLE `users` MODIFY COLUMN `phone_number` VARCHAR(20) NOT NULL');
    }
};
