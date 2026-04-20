<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Card extends Model
{
    protected $table = 'cards';

    protected $fillable = [
        'user_id',
        'account_id',
        'card_number_masked',
        'card_number_encrypted',
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

    // Sembunyikan dari response JSON biasa
    protected $hidden = [
        'card_number_encrypted',
    ];

    /**
     * Dekripsi nomor kartu penuh
     */
    public function getFullCardNumber(): ?string
    {
        if (!$this->card_number_encrypted) {
            return null;
        }
        try {
            return Crypt::decryptString($this->card_number_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeType($query, $type)
    {
        return $query->where('card_type', $type);
    }
}
