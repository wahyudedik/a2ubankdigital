<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'bank_id' => $this->bank_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'last_login_at' => $this->last_login_at,
            'nik' => $this->customerProfile?->nik,
            'mother_maiden_name' => $this->customerProfile?->mother_maiden_name,
            'pob' => $this->customerProfile?->pob,
            'dob' => $this->customerProfile?->dob,
            'gender' => $this->customerProfile?->gender,
            'address_ktp' => $this->customerProfile?->address_ktp,
            'address_domicile' => $this->customerProfile?->address_domicile,
            'occupation' => $this->customerProfile?->occupation,
            'kyc_status' => $this->customerProfile?->kyc_status,
        ];
    }
}
