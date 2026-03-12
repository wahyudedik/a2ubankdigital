<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositProduct extends Model
{
    protected $table = 'deposit_products';

    protected $fillable = [
        'product_name',
        'product_code',
        'description',
        'min_amount',
        'interest_rate_pa',
        'tenor_months',
        'is_active'
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'interest_rate_pa' => 'decimal:2',
        'tenor_months' => 'integer',
        'is_active' => 'boolean'
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
}
