<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_visit_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->unsignedBigInteger('collector_id')->nullable();
            $table->date('visit_date')->nullable();
            $table->enum('outcome', ['bertemu', 'tidak_bertemu', 'janji_bayar', 'lainnya']);
            $table->text('notes')->nullable();
            $table->date('next_action_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_visit_reports');
    }
};
