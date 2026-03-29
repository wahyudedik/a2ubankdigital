<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'product_code',
        'min_amount',
        'max_amount',
        'interest_rate_pa',
        'min_tenor',
        'max_tenor',
        'tenor_unit',
        'late_payment_fee',
        'is_active'
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'interest_rate_pa' => 'decimal:4',
        'late_payment_fee' => 'decimal:2'
    ];

    /**
     * Get loans using this product
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}