<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class CardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            [
                'id' => 1,
                'user_id' => 85,
                'account_id' => 51,
                'card_number_masked' => '4562-****-****-4443',
                'card_number_full' => '4562-8831-9274-4443',
            ],
            [
                'id' => 2,
                'user_id' => 89,
                'account_id' => 54,
                'card_number_masked' => '4562-****-****-7821',
                'card_number_full' => '4562-5519-3047-7821',
            ],
            [
                'id' => 3,
                'user_id' => 90,
                'account_id' => 55,
                'card_number_masked' => '4562-****-****-3319',
                'card_number_full' => '4562-2784-6103-3319',
            ],
            [
                'id' => 4,
                'user_id' => 93,
                'account_id' => 56,
                'card_number_masked' => '4562-****-****-9905',
                'card_number_full' => '4562-6620-1857-9905',
            ],
        ];

        foreach ($cards as $card) {
            DB::table('cards')->insert([
                'id'                     => $card['id'],
                'user_id'                => $card['user_id'],
                'account_id'             => $card['account_id'],
                'card_number_masked'     => $card['card_number_masked'],
                'card_number_encrypted'  => Crypt::encryptString($card['card_number_full']),
                'card_type'              => 'debit',
                'status'                 => 'active',
                'daily_limit'            => 5000000.00,
                'requested_at'           => now(),
                'activated_at'           => now(),
                'expiry_date'            => '2029-04-30',
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }
    }
}
