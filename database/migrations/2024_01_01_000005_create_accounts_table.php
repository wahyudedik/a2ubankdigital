<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('account_number', 20)->unique();
            $table->enum('account_type', ['TABUNGAN', 'PINJAMAN', 'DEPOSITO', 'TABUNGAN_RENCANA']);
            $table->decimal('balance', 20, 2)->default(0);
            $table->enum('status', ['PENDING', 'ACTIVE', 'DORMANT', 'CLOSED', 'MATURED'])->default('ACTIVE');
            $table->decimal('credit_limit', 20, 2)->nullable();
            $table->unsignedBigInteger('deposit_product_id')->nullable();
            $table->date('maturity_date')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('account_number');
            $table->index('status');
        });

        // Create trigger for auto-generating account numbers
        DB::unprepared("
            CREATE TRIGGER trg_generate_account_number BEFORE INSERT ON accounts FOR EACH ROW
            BEGIN
                DECLARE prefix VARCHAR(3);
                IF NEW.account_number IS NULL OR NEW.account_number = '' THEN
                    IF NEW.account_type = 'TABUNGAN' THEN
                        SET prefix = '110';
                    ELSEIF NEW.account_type = 'PINJAMAN' THEN
                        SET prefix = '210';
                    ELSEIF NEW.account_type = 'DEPOSITO' THEN
                        SET prefix = '310';
                    ELSE
                        SET prefix = '120';
                    END IF;
                    SET @user_id_padded = LPAD(NEW.user_id, 7, '0');
                    SET @random_part = LPAD(FLOOR(RAND() * 999), 3, '0');
                    SET NEW.account_number = CONCAT(prefix, @user_id_padded, @random_part);
                END IF;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_generate_account_number');
        Schema::dropIfExists('accounts');
    }
};
