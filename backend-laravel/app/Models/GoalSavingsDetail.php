<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalSavingsDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'account_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'goal_name',
        'goal_amount',
        'target_date',
        'autodebit_day',
        'autodebit_amount',
        'from_account_id'
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'autodebit_amount' => 'decimal:2',
        'target_date' => 'date',
        'autodebit_day' => 'integer'
    ];

    /**
     * Get the goal savings account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the source account for autodebit
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    /**
     * Calculate progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->goal_amount <= 0) {
            return 0;
        }
        
        $currentBalance = $this->account->balance ?? 0;
        $progress = ($currentBalance / $this->goal_amount) * 100;
        
        return min($progress, 100);
    }

    /**
     * Calculate remaining amount to reach goal
     */
    public function getRemainingAmountAttribute(): float
    {
        $currentBalance = $this->account->balance ?? 0;
        $remaining = $this->goal_amount - $currentBalance;
        
        return max($remaining, 0);
    }

    /**
     * Calculate days remaining to target date
     */
    public function getDaysRemainingAttribute(): int
    {
        return now()->diffInDays($this->target_date, false);
    }

    /**
     * Check if goal is achieved
     */
    public function getIsAchievedAttribute(): bool
    {
        $currentBalance = $this->account->balance ?? 0;
        return $currentBalance >= $this->goal_amount;
    }

    /**
     * Check if autodebit should run today
     */
    public function shouldAutodebitToday(): bool
    {
        $today = now();
        
        // Check if today matches the autodebit day
        if ($this->autodebit_day !== $today->day) {
            return false;
        }
        
        // Check if goal is not yet achieved
        if ($this->is_achieved) {
            return false;
        }
        
        // Check if target date has not passed
        if ($this->target_date < $today->toDateString()) {
            return false;
        }
        
        return true;
    }
}