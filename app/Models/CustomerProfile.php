<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'unit_id',
        'nik',
        'mother_maiden_name',
        'pob',
        'dob',
        'gender',
        'address_ktp',
        'address_domicile',
        'occupation',
        'monthly_income',
        'ktp_image_path',
        'selfie_image_path',
        'kyc_status',
        'kyc_notes',
        'kyc_verified_at',
        'kyc_verified_by',
        'loyalty_points',
    ];

    protected $casts = [
        'dob' => 'date:Y-m-d',
        'monthly_income' => 'decimal:2',
        'kyc_verified_at' => 'datetime',
        'loyalty_points' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'kyc_verified_by');
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('kyc_status', 'VERIFIED');
    }

    public function scopePending($query)
    {
        return $query->where('kyc_status', 'PENDING');
    }

    // Helper methods
    public function isVerified(): bool
    {
        return $this->kyc_status === 'VERIFIED';
    }

    public function getAge(): int
    {
        return $this->dob->age;
    }

    public function getKtpImageUrl(): ?string
    {
        return $this->ktp_image_path ? asset('storage/' . $this->ktp_image_path) : null;
    }

    public function getSelfieImageUrl(): ?string
    {
        return $this->selfie_image_path ? asset('storage/' . $this->selfie_image_path) : null;
    }
}
