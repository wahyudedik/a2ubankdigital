<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalAccount extends Model
{
    protected $table = 'withdrawal_accounts';

    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
        'account_name'
    ];

    protected $casts = [];

    protected $hidden = [];

    protected $appends = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }
}