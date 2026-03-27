<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'installment_number',
        'due_date',
        'amount_due',
        'penalty_amount',
        'status',
        'payment_date',
        'transaction_id'
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'amount_due' => 'decimal:2',
        'penalty_amount' => 'decimal:2'
    ];

    /**
     * Get the loan this installment belongs to
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the payment transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scope for pending installments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope for paid installments
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }

    /**
     * Scope for overdue installments
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'OVERDUE');
    }
}