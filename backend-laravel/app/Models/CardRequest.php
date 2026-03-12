<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardRequest extends Model
{
    protected $table = 'card_requests';

    protected $fillable = [
        'user_id',
        'card_type',
        'delivery_address',
        'reason',
        'status',
        'processed_by',
        'processed_at',
        'rejection_reason'
    ];

    protected $casts = [
        'processed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
}