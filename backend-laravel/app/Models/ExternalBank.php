<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalBank extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'bank_code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Scope for active banks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get bank by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('bank_code', $code)->first();
    }
}