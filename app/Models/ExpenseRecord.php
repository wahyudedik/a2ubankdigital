<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'category_id',
        'amount',
        'description',
        'expense_date',
        'receipt_path',
        'is_auto_imported',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'is_auto_imported' => 'boolean',
    ];

    /**
     * Get the user that owns the record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the linked transaction (if auto-imported).
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
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
        return $query->whereYear('expense_date', $year)
                     ->whereMonth('expense_date', $month);
    }

    /**
     * Scope for auto-imported records.
     */
    public function scopeAutoImported($query)
    {
        return $query->where('is_auto_imported', true);
    }

    /**
     * Scope for manual records.
     */
    public function scopeManual($query)
    {
        return $query->where('is_auto_imported', false);
    }

    /**
     * Check if record is linked to a transaction.
     */
    public function isLinkedToTransaction(): bool
    {
        return !is_null($this->transaction_id);
    }
}
