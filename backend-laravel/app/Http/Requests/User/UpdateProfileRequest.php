<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'address_domicile' => 'sometimes|string',
            'occupation' => 'sometimes|string|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.string' => 'Nama lengkap harus berupa teks.',
            'full_name.max' => 'Nama lengkap maksimal 255 karakter.',
            'phone_number.string' => 'Nomor telepon harus berupa teks.',
            'phone_number.max' => 'Nomor telepon maksimal 20 karakter.',
            'address_domicile.string' => 'Alamat domisili harus berupa teks.',
            'occupation.string' => 'Pekerjaan harus berupa teks.',
            'occupation.max' => 'Pekerjaan maksimal 100 karakter.'
        ];
    }
}
