<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    protected $table = 'withdrawal_requests';

    protected $fillable = [
        'user_id',
        'withdrawal_account_id',
        'amount',
        'purpose',
        'status',
        'processed_by',
        'processed_at',
        'cancelled_at',
        'rejection_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawalAccount()
    {
        return $this->belongsTo(WithdrawalAccount::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    /**
     * Scope for processed requests
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'PROCESSED');
    }
}