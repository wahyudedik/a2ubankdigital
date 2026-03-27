<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointsHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'points',
        'description',
        'transaction_id',
        'created_at'
    ];

    protected $casts = [
        'points' => 'integer',
        'created_at' => 'datetime'
    ];

    /**
     * Get the user that owns the loyalty points
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related transaction if any
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scope for earned points (positive)
     */
    public function scopeEarned($query)
    {
        return $query->where('points', '>', 0);
    }

    /**
     * Scope for redeemed points (negative)
     */
    public function scopeRedeemed($query)
    {
        return $query->where('points', '<', 0);
    }

    /**
     * Create points entry for transaction
     */
    public static function createFromTransaction(Transaction $transaction): ?self
    {
        // Calculate points based on transaction amount (1 point per 1000 rupiah)
        $points = floor($transaction->amount / 1000);
        
        if ($points <= 0) {
            return null;
        }

        return static::create([
            'user_id' => $transaction->fromAccount->user_id ?? $transaction->toAccount->user_id,
            'points' => $points,
            'description' => 'Poin dari transaksi ' . $transaction->transaction_type,
            'transaction_id' => $transaction->id,
            'created_at' => now()
        ]);
    }

    /**
     * Create points entry for redemption
     */
    public static function createRedemption(int $userId, int $points, string $description): self
    {
        return static::create([
            'user_id' => $userId,
            'points' => -abs($points), // Ensure negative for redemption
            'description' => $description,
            'transaction_id' => null,
            'created_at' => now()
        ]);
    }
}