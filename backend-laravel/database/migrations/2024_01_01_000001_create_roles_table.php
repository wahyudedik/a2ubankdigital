<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('role_name', 50);
            $table->timestamps();
        });

        // Insert default roles
        DB::table('roles')->insert([
            ['id' => 1, 'role_name' => 'SUPER_ADMIN'],
            ['id' => 2, 'role_name' => 'BRANCH_MANAGER'],
            ['id' => 3, 'role_name' => 'TELLER'],
            ['id' => 4, 'role_name' => 'LOAN_OFFICER'],
            ['id' => 5, 'role_name' => 'CUSTOMER_SERVICE'],
            ['id' => 6, 'role_name' => 'MARKETING'],
            ['id' => 7, 'role_name' => 'DEBT_COLLECTOR'],
            ['id' => 8, 'role_name' => 'AUDITOR'],
            ['id' => 9, 'role_name' => 'CUSTOMER'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
