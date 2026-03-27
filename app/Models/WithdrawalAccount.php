<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalAccount extends Model
{
    protected $table = 'withdrawal_accounts';

    protected $fillable = [
        'user_id',
        'bank_code',
        'bank_name',
        'account_number',
        'account_holder_name',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    protected $hidden = [
        'account_number' // Hide for security
    ];

    protected $appends = [
        'masked_account_number'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    /**
     * Get masked account number
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        if (!$this->account_number) {
            return '';
        }

        $length = strlen($this->account_number);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($this->account_number, 0, 2) . str_repeat('*', $length - 4) . substr($this->account_number, -2);
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}