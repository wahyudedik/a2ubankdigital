<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Default expense categories for all customers.
     */
    private array $defaultCategories = [
        ['name' => 'Makanan & Minuman', 'icon' => '🍔', 'color' => '#FF6B6B'],
        ['name' => 'Transportasi', 'icon' => '🚗', 'color' => '#4ECDC4'],
        ['name' => 'Rumah Tangga', 'icon' => '🏠', 'color' => '#45B7D1'],
        ['name' => 'Belanja', 'icon' => '🛒', 'color' => '#96CEB4'],
        ['name' => 'Hiburan', 'icon' => '🎭', 'color' => '#FFEAA7'],
        ['name' => 'Kesehatan', 'icon' => '🏥', 'color' => '#DDA0DD'],
        ['name' => 'Pendidikan', 'icon' => '📚', 'color' => '#98D8C8'],
        ['name' => 'Tabungan & Investasi', 'icon' => '💰', 'color' => '#F7DC6F'],
        ['name' => 'Lainnya', 'icon' => '🎁', 'color' => '#BB8FCE'],
    ];

    public function run(): void
    {
        // Create default categories for all existing customers
        $customers = User::where('role_id', 9)->get(); // role_id 9 = customer

        foreach ($customers as $customer) {
            foreach ($this->defaultCategories as $category) {
                ExpenseCategory::firstOrCreate(
                    [
                        'user_id' => $customer->id,
                        'name' => $category['name'],
                    ],
                    [
                        'icon' => $category['icon'],
                        'color' => $category['color'],
                        'is_default' => true,
                    ]
                );
            }
        }
    }
}
