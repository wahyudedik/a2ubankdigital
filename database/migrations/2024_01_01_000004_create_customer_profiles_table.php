<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('nik', 16)->unique();
            $table->string('mother_maiden_name');
            $table->string('pob'); // Place of birth
            $table->date('dob'); // Date of birth
            $table->enum('gender', ['L', 'P']); // L=Laki-laki, P=Perempuan
            $table->text('address_ktp');
            $table->text('address_domicile')->nullable();
            $table->string('occupation', 100)->nullable();
            $table->decimal('monthly_income', 15, 2)->nullable();
            $table->string('ktp_image_path')->nullable();
            $table->string('selfie_image_path')->nullable();
            $table->enum('kyc_status', ['PENDING', 'VERIFIED', 'REJECTED'])->default('PENDING');
            $table->text('kyc_notes')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();
            $table->unsignedBigInteger('kyc_verified_by')->nullable();
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->timestamps();

            $table->index('nik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
