<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'year',
        'month',
        'budget_amount',
        'alert_threshold',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'year' => 'integer',
        'month' => 'integer',
        'alert_threshold' => 'integer',
    ];

    /**
     * Get the user that owns the budget.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the expense category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Scope for specific month and year.
     */
    public function scopeForMonth($query, $year, $month)
    {
        return $query->where('year', $year)
                     ->where('month', $month);
    }

    /**
     * Scope for current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('year', now()->year)
                     ->where('month', now()->month);
    }

    /**
     * Calculate total spending for this budget's category in the given month.
     */
    public function getTotalSpentAttribute(): float
    {
        return ExpenseRecord::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->forMonth($this->year, $this->month)
            ->sum('amount');
    }

    /**
     * Get remaining budget amount.
     */
    public function getRemainingAttribute(): float
    {
        return max(0, (float) $this->budget_amount - $this->total_spent);
    }

    /**
     * Get usage percentage.
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->budget_amount <= 0) {
            return 0;
        }

        return round(($this->total_spent / $this->budget_amount) * 100, 1);
    }

    /**
     * Check if alert should be triggered.
     */
    public function shouldAlert(): bool
    {
        return $this->usage_percentage >= $this->alert_threshold;
    }

    /**
     * Check if budget is exceeded.
     */
    public function isExceeded(): bool
    {
        return $this->total_spent > $this->budget_amount;
    }
}
