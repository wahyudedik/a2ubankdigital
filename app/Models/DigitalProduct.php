<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalProduct extends Model
{
    protected $table = 'digital_products';

    protected $fillable = [
        'product_code',
        'product_name',
        'category',
        'provider',
        'price',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for specific provider
     */
    public function scopeProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }
}