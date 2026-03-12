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
        'min_amount',
        'max_amount',
        'interest_rate_pa',
        'min_tenor',
        'max_tenor',
        'tenor_unit'
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'interest_rate_pa' => 'decimal:4'
    ];

    /**
     * Get loans using this product
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}