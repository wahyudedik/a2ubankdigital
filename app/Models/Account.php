<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_number',
        'account_type',
        'balance',
        'status',
        'credit_limit',
        'deposit_product_id',
        'maturity_date'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'maturity_date' => 'date'
    ];

    /**
     * Get the user that owns the account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the deposit product if this is a deposit account
     */
    public function depositProduct(): BelongsTo
    {
        return $this->belongsTo(DepositProduct::class);
    }

    /**
     * Get transactions where this account is the source
     */
    public function outgoingTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_account_id');
    }

    /**
     * Get transactions where this account is the destination
     */
    public function incomingTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_account_id');
    }

    /**
     * Get all transactions for this account
     */
    public function transactions()
    {
        return Transaction::where('from_account_id', $this->id)
                         ->orWhere('to_account_id', $this->id)
                         ->orderBy('created_at', 'desc');
    }

    /**
     * Get goal savings details if this is a goal savings account
     */
    public function goalSavingsDetail(): HasOne
    {
        return $this->hasOne(GoalSavingsDetail::class, 'account_id');
    }

    /**
     * Get loans associated with this account
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'account_id');
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope for accounts by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Check if account has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get available balance (considering credit limit for loan accounts)
     */
    public function getAvailableBalanceAttribute(): float
    {
        if ($this->account_type === 'PINJAMAN' && $this->credit_limit) {
            return $this->balance + $this->credit_limit;
        }
        
        return $this->balance;
    }

    /**
     * Check if account is matured (for deposit accounts)
     */
    public function getIsMaturedAttribute(): bool
    {
        if ($this->account_type !== 'DEPOSITO' || !$this->maturity_date) {
            return false;
        }
        
        return now()->toDateString() >= $this->maturity_date;
    }
}