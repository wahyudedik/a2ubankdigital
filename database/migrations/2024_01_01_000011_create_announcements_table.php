<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->enum('type', ['info', 'warning', 'promo'])->default('info');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('created_by')->unsigned();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
