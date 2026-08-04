<?php

namespace App\Services;

use App\Models\ExpenseCategory;
use App\Models\ExpenseRecord;
use App\Models\MonthlyBudget;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpenseService
{
    /**
     * Get or create default categories for a user.
     */
    public function getOrCreateDefaultCategories(User $user): array
    {
        $defaultCategories = [
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

        $categories = [];
        foreach ($defaultCategories as $category) {
            $categories[] = ExpenseCategory::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $category['name'],
                ],
                [
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'is_default' => true,
                ]
            );
        }

        return $categories;
    }

    /**
     * Auto-import transaction as expense record.
     */
    public function autoImportTransaction(Transaction $transaction): ?ExpenseRecord
    {
        $user = $transaction->fromAccount?->user;
        if (!$user || $user->role_id != 9) { // Only for customers
            return null;
        }

        // Check if already imported
        $existing = ExpenseRecord::where('transaction_id', $transaction->id)->first();
        if ($existing) {
            return null;
        }

        // Auto-categorize based on transaction type
        $categoryId = $this->autoCategorizeTransaction($transaction, $user);

        return ExpenseRecord::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'category_id' => $categoryId,
            'amount' => $transaction->amount,
            'description' => $transaction->description ?? $transaction->transaction_type,
            'expense_date' => $transaction->created_at->toDateString(),
            'is_auto_imported' => true,
        ]);
    }

    /**
     * Auto-categorize transaction based on type and description.
     */
    private function autoCategorizeTransaction(Transaction $transaction, User $user): int
    {
        $description = strtolower($transaction->description ?? '');
        $type = $transaction->transaction_type;

        // Get user's categories
        $categories = ExpenseCategory::where('user_id', $user->id)->get();

        // Category mapping keywords
        $categoryKeywords = [
            'Makanan & Minuman' => ['makan', 'minum', 'restoran', 'cafe', 'kopi', 'food', 'drink', 'groceries', ' Swalayan', 'Indomaret', 'Alfamart'],
            'Transportasi' => ['bensin', 'parkir', 'tol', 'ojek', 'grab', 'gojek', 'transport', 'parking', 'fuel'],
            'Rumah Tangga' => ['listrik', 'air', 'internet', 'sewa', 'kos', 'telepon', 'pln', 'pdam'],
            'Hiburan' => ['netflix', 'spotify', 'game', 'bioskop', 'movie', 'entertainment', 'streaming'],
            'Kesehatan' => ['obat', 'dokter', 'rumah sakit', 'apotek', 'kesehatan', 'health'],
            'Pendidikan' => ['kursus', 'sekolah', 'universitas', 'buku', 'pendidikan', 'education'],
            'Tabungan & Investasi' => ['tabungan', 'investasi', 'saham', 'reksadana', 'deposito', 'saving'],
        ];

        // Find matching category
        foreach ($categoryKeywords as $categoryName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($description, $keyword)) {
                    $category = $categories->where('name', $categoryName)->first();
                    if ($category) {
                        return $category->id;
                    }
                }
            }
        }

        // Default to "Lainnya"
        $lainnya = $categories->where('name', 'Lainnya')->first();
        return $lainnya?->id ?? 1;
    }

    /**
     * Get monthly summary for a user.
     */
    public function getMonthlySummary(User $user, int $year, int $month): array
    {
        $categories = ExpenseCategory::where('user_id', $user->id)->get();

        $summary = [];
        $totalSpent = 0;
        $totalBudget = 0;

        foreach ($categories as $category) {
            $spent = ExpenseRecord::where('user_id', $user->id)
                ->where('category_id', $category->id)
                ->forMonth($year, $month)
                ->sum('amount');

            $budget = MonthlyBudget::where('user_id', $user->id)
                ->where('category_id', $category->id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            $budgetAmount = $budget?->budget_amount ?? 0;
            $usagePercentage = $budgetAmount > 0 ? round(($spent / $budgetAmount) * 100, 1) : 0;
            $remaining = max(0, $budgetAmount - $spent);

            $summary[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_icon' => $category->icon,
                'category_color' => $category->color,
                'spent' => (float) $spent,
                'budget' => (float) $budgetAmount,
                'remaining' => $remaining,
                'usage_percentage' => $usagePercentage,
                'alert_threshold' => $budget?->alert_threshold ?? 80,
                'is_over_budget' => $budgetAmount > 0 && $spent > $budgetAmount,
                'should_alert' => $budget && $usagePercentage >= $budget->alert_threshold,
            ];

            $totalSpent += $spent;
            $totalBudget += $budgetAmount;
        }

        return [
            'year' => $year,
            'month' => $month,
            'total_spent' => $totalSpent,
            'total_budget' => $totalBudget,
            'remaining' => max(0, $totalBudget - $totalSpent),
            'categories' => $summary,
        ];
    }

    /**
     * Get expense trend for the last N months.
     */
    public function getExpenseTrend(User $user, int $months = 6): array
    {
        $trend = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $year = $date->year;
            $month = $date->month;

            $totalSpent = ExpenseRecord::where('user_id', $user->id)
                ->forMonth($year, $month)
                ->sum('amount');

            $totalBudget = MonthlyBudget::where('user_id', $user->id)
                ->forMonth($year, $month)
                ->sum('budget_amount');

            $trend[] = [
                'year' => $year,
                'month' => $month,
                'month_name' => $date->format('M Y'),
                'total_spent' => (float) $totalSpent,
                'total_budget' => (float) $totalBudget,
            ];
        }

        return $trend;
    }

    /**
     * Get insights and recommendations for a user.
     */
    public function getInsights(User $user): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $lastMonth = now()->subMonth();

        $insights = [];

        // Current month summary
        $currentSummary = $this->getMonthlySummary($user, $currentYear, $currentMonth);
        $lastMonthSummary = $this->getMonthlySummary($user, $lastMonth->year, $lastMonth->month);

        // Check overspending categories
        foreach ($currentSummary['categories'] as $category) {
            if ($category['is_over_budget']) {
                $overspent = $category['spent'] - $category['budget'];
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'Budget Terlampaui',
                    'message' => "Pengeluaran {$category['category_name']} melebihi budget sebesar Rp " . number_format($overspent, 0, ',', '.'),
                    'category' => $category['category_name'],
                ];
            } elseif ($category['should_alert']) {
                $insights[] = [
                    'type' => 'alert',
                    'title' => 'Mendekati Batas Budget',
                    'message' => "Pengeluaran {$category['category_name']} sudah mencapai {$category['usage_percentage']}% dari budget.",
                    'category' => $category['category_name'],
                ];
            }
        }

        // Compare with last month
        if ($lastMonthSummary['total_spent'] > 0) {
            $diff = $currentSummary['total_spent'] - $lastMonthSummary['total_spent'];
            $percentage = round(($diff / $lastMonthSummary['total_spent']) * 100, 1);

            if ($diff > 0) {
                $insights[] = [
                    'type' => 'info',
                    'title' => 'Pengeluaran Meningkat',
                    'message' => "Pengeluaran bulan ini naik {$percentage}% dari bulan lalu.",
                ];
            } elseif ($diff < 0) {
                $insights[] = [
                    'type' => 'success',
                    'title' => 'Pengeluaran Berkurang',
                    'message' => "Pengeluaran bulan ini turun " . abs($percentage) . "% dari bulan lalu. Bagus!",
                ];
            }
        }

        // Top spending category
        $topCategory = collect($currentSummary['categories'])
            ->sortByDesc('spent')
            ->first();

        if ($topCategory && $topCategory['spent'] > 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Pengeluaran Terbesar',
                'message' => "Kategori {$topCategory['category_name']} adalah pengeluaran terbesar bulan ini.",
                'amount' => $topCategory['spent'],
            ];
        }

        return $insights;
    }
}
