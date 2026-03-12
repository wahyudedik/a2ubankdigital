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

    // Role constants
    const SUPER_ADMIN = 1;
    const BRANCH_MANAGER = 2;
    const TELLER = 3;
    const LOAN_OFFICER = 4;
    const CUSTOMER_SERVICE = 5;
    const MARKETING = 6;
    const DEBT_COLLECTOR = 7;
    const AUDITOR = 8;
    const CUSTOMER = 9;
}
