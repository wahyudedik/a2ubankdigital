<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'loan_product_id',
        'loan_amount',
        'interest_rate_pa',
        'tenor',
        'tenor_unit',
        'monthly_installment',
        'total_interest',
        'total_repayment',
        'purpose',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'disbursed_at',
        'disbursed_by',
        'first_payment_date'
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'interest_rate_pa' => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'total_interest' => 'decimal:2',
        'total_repayment' => 'decimal:2',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'first_payment_date' => 'date'
    ];

    /**
     * Get the user that owns the loan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account associated with the loan
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the loan product
     */
    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }

    /**
     * Get loan installments
     */
    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    /**
     * Scope for active loans
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope for pending loans
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }
}