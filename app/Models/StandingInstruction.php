<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandingInstruction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_account_id',
        'to_account_number',
        'to_bank_code',
        'amount',
        'description',
        'frequency',
        'execution_day',
        'start_date',
        'end_date',
        'status',
        'last_executed'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_executed' => 'date',
        'execution_day' => 'integer'
    ];

    /**
     * Get the user that owns the standing instruction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source account
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    /**
     * Scope for active instructions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope for paused instructions
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'PAUSED');
    }

    /**
     * Scope for ended instructions
     */
    public function scopeEnded($query)
    {
        return $query->where('status', 'ENDED');
    }

    /**
     * Scope for instructions due for execution
     */
    public function scopeDueForExecution($query)
    {
        $today = now();
        
        return $query->where('status', 'ACTIVE')
                    ->where('start_date', '<=', $today->toDateString())
                    ->where(function($q) use ($today) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $today->toDateString());
                    })
                    ->where(function($q) use ($today) {
                        // Monthly frequency
                        $q->where(function($monthly) use ($today) {
                            $monthly->where('frequency', 'MONTHLY')
                                   ->where('execution_day', $today->day);
                        })
                        // Weekly frequency  
                        ->orWhere(function($weekly) use ($today) {
                            $weekly->where('frequency', 'WEEKLY')
                                   ->where('execution_day', $today->dayOfWeek);
                        });
                    });
    }

    /**
     * Check if instruction should execute today
     */
    public function shouldExecuteToday(): bool
    {
        $today = now();
        
        // Check if active and within date range
        if ($this->status !== 'ACTIVE') {
            return false;
        }
        
        if ($this->start_date > $today->toDateString()) {
            return false;
        }
        
        if ($this->end_date && $this->end_date < $today->toDateString()) {
            return false;
        }
        
        // Check if already executed today
        if ($this->last_executed && $this->last_executed === $today->toDateString()) {
            return false;
        }
        
        // Check frequency
        if ($this->frequency === 'MONTHLY') {
            return $this->execution_day === $today->day;
        } elseif ($this->frequency === 'WEEKLY') {
            return $this->execution_day === $today->dayOfWeek;
        }
        
        return false;
    }
}