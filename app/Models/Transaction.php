<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'from_account_id',
        'to_account_id',
        'transaction_type',
        'amount',
        'fee',
        'description',
        'status',
        'reference_number',
        'external_bank_code',
        'external_account_number',
        'external_account_name'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2'
    ];

    /**
     * Get the source account
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    /**
     * Get the destination account
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    /**
     * Scope for successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'SUCCESS');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'FAILED');
    }

    /**
     * Scope for transactions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }
}