<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_collection_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_installment_id')->nullable();
            $table->unsignedBigInteger('collector_id')->nullable();
            $table->integer('assigned_by')->unsigned();
            $table->enum('status', ['assigned', 'in_progress', 'closed'])->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_collection_assignments');
    }
};
