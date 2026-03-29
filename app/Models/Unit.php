<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_name',
        'unit_code',
        'unit_type',
        'parent_id',
        'address',
        'phone',
        'email',
        'latitude',
        'longitude',
        'status'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    /**
     * Get the parent unit (for units under branches)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    /**
     * Get child units (for branches that have units)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    /**
     * Get customer profiles assigned to this unit
     */
    public function customerProfiles(): HasMany
    {
        return $this->hasMany(CustomerProfile::class, 'unit_id');
    }

    /**
     * Scope for active units
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope for branches only
     */
    public function scopeBranches($query)
    {
        return $query->where('unit_type', 'KANTOR_CABANG');
    }

    /**
     * Scope for units only
     */
    public function scopeUnits($query)
    {
        return $query->where('unit_type', 'KANTOR_KAS');
    }
}