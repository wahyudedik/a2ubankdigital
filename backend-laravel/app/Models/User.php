<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'bank_id',
        'role_id',
        'full_name',
        'email',
        'phone_number',
        'password_hash',
        'pin_hash',
        'status',
        'failed_login_attempts',
        'last_login_at',
        'last_login_ip',
        'is_2fa_enabled',
        'two_factor_secret',
    ];

    protected $hidden = [
        'password_hash',
        'pin_hash',
        'two_factor_secret',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'is_2fa_enabled' => 'boolean',
        'failed_login_attempts' => 'integer',
    ];

    // Override default password column untuk menggunakan password_hash
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, Account::class, 'user_id', 'from_account_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function withdrawalAccounts()
    {
        return $this->hasMany(WithdrawalAccount::class);
    }

    public function topupRequests()
    {
        return $this->hasMany(TopupRequest::class);
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function loginHistory()
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function scheduledTransfers()
    {
        return $this->hasMany(ScheduledTransfer::class);
    }

    public function standingInstructions()
    {
        return $this->hasMany(StandingInstruction::class);
    }

    public function loyaltyPointsHistory()
    {
        return $this->hasMany(LoyaltyPointsHistory::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(SecureMessage::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(SecureMessage::class, 'recipient_id');
    }

    public function createdAnnouncements()
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeCustomers($query)
    {
        return $query->where('role_id', 9); // CUSTOMER role
    }

    public function scopeStaff($query)
    {
        return $query->where('role_id', '<', 9);
    }

    // Helper methods
    public function isCustomer(): bool
    {
        return $this->role_id === 9;
    }

    public function isStaff(): bool
    {
        return $this->role_id < 9;
    }

    public function isAdmin(): bool
    {
        return $this->role_id === 1;
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role->role_name === $roleName;
    }

    public function canAccessAdmin(): bool
    {
        return in_array($this->role_id, [1, 2, 3, 4, 5, 6, 7, 8]);
    }

    public function incrementFailedLogins(): void
    {
        $this->increment('failed_login_attempts');
    }

    public function resetFailedLogins(): void
    {
        $this->update(['failed_login_attempts' => 0]);
    }

    public function updateLastLogin(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    public function verifyPin(string $pin): bool
    {
        return password_verify($pin, $this->pin_hash);
    }

    public function setPin(string $pin): void
    {
        $this->update(['pin_hash' => bcrypt($pin)]);
    }
}
