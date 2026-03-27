<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $table = 'cards';

    protected $fillable = [
        'user_id',
        'card_number',
        'card_type',
        'status',
        'daily_limit',
        'monthly_limit',
        'issued_at',
        'expires_at'
    ];

    protected $casts = [
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    protected $hidden = [
        'card_number' // Hide full card number for security
    ];

    protected $appends = [
        'masked_card_number'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get masked card number
     */
    public function getMaskedCardNumberAttribute(): string
    {
        if (!$this->card_number) {
            return '';
        }

        return substr($this->card_number, 0, 4) . ' **** **** ' . substr($this->card_number, -4);
    }

    /**
     * Scope for active cards
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope for specific card type
     */
    public function scopeType($query, $type)
    {
        return $query->where('card_type', $type);
    }
}