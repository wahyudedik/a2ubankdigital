<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secure_messages', function (Blueprint $table) {
            $table->id();
            $table->string('thread_id', 50);
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secure_messages');
    }
};
