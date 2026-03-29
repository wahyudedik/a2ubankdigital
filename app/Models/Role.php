<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['role_name'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Role constants (must match CheckRole middleware)
    const SUPER_ADMIN = 1;
    const ADMIN = 2;              // Kepala Cabang
    const MANAGER = 3;            // Kepala Unit
    const MARKETING = 4;          // Marketing
    const TELLER = 5;             // Teller
    const CS = 6;                 // Customer Service
    const ANALYST = 7;            // Analis Kredit
    const DEBT_COLLECTOR = 8;     // Debt Collector
    const CUSTOMER = 9;           // Nasabah
}
