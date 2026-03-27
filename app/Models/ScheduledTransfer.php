<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_account_id',
        'to_account_number',
        'amount',
        'description',
        'scheduled_date',
        'status',
        'executed_at',
        'failure_reason',
        'transaction_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'scheduled_date' => 'date',
        'executed_at' => 'datetime'
    ];

    /**
     * Get the user that owns the scheduled transfer
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
     * Get the related transaction if executed
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scope for pending transfers
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope for executed transfers
     */
    public function scopeExecuted($query)
    {
        return $query->where('status', 'EXECUTED');
    }

    /**
     * Scope for failed transfers
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'FAILED');
    }

    /**
     * Scope for transfers due today or earlier
     */
    public function scopeDue($query)
    {
        return $query->where('scheduled_date', '<=', now()->toDateString())
                    ->where('status', 'PENDING');
    }
}