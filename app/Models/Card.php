<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $table = 'cards';

    protected $fillable = [
        'user_id',
        'account_id',
        'card_number_masked',
        'card_type',
        'status',
        'daily_limit',
        'requested_at',
        'activated_at',
        'expiry_date'
    ];

    protected $casts = [
        'daily_limit' => 'decimal:2',
        'requested_at' => 'datetime',
        'activated_at' => 'datetime',
        'expiry_date' => 'date'
    ];

    protected $hidden = [];

    protected $appends = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope for active cards
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for specific card type
     */
    public function scopeType($query, $type)
    {
        return $query->where('card_type', $type);
    }
}