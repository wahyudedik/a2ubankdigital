<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the status enum to include CANCELLED and use uppercase
        DB::statement("ALTER TABLE `account_closure_requests` MODIFY `status` ENUM('PENDING', 'APPROVED', 'REJECTED', 'CANCELLED') NOT NULL DEFAULT 'PENDING'");
        
        // Add admin_notes column if it doesn't exist
        if (!Schema::hasColumn('account_closure_requests', 'admin_notes')) {
            Schema::table('account_closure_requests', function (Blueprint $table) {
                $table->text('admin_notes')->nullable()->after('processed_at');
            });
        }
        
        // Make user_id NOT NULL and add foreign key if not exists
        Schema::table('account_closure_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            
            // Add indexes if they don't exist
            if (!$this->indexExists('account_closure_requests', 'account_closure_requests_user_id_index')) {
                $table->index('user_id');
            }
            if (!$this->indexExists('account_closure_requests', 'account_closure_requests_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('account_closure_requests', 'account_closure_requests_requested_at_index')) {
                $table->index('requested_at');
            }
        });
        
        // Make reason NOT NULL
        DB::statement("ALTER TABLE `account_closure_requests` MODIFY `reason` TEXT NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert status enum
        DB::statement("ALTER TABLE `account_closure_requests` MODIFY `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
        
        // Drop admin_notes if it exists
        if (Schema::hasColumn('account_closure_requests', 'admin_notes')) {
            Schema::table('account_closure_requests', function (Blueprint $table) {
                $table->dropColumn('admin_notes');
            });
        }
        
        // Drop indexes
        Schema::table('account_closure_requests', function (Blueprint $table) {
            if ($this->indexExists('account_closure_requests', 'account_closure_requests_user_id_index')) {
                $table->dropIndex('account_closure_requests_user_id_index');
            }
            if ($this->indexExists('account_closure_requests', 'account_closure_requests_status_index')) {
                $table->dropIndex('account_closure_requests_status_index');
            }
            if ($this->indexExists('account_closure_requests', 'account_closure_requests_requested_at_index')) {
                $table->dropIndex('account_closure_requests_requested_at_index');
            }
        });
    }
    
    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};
