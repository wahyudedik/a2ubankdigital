<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecureMessage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'thread_id',
        'sender_id',
        'recipient_id',
        'message',
        'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime'
    ];

    /**
     * Get the sender of the message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the recipient of the message
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Scope for messages in a thread
     */
    public function scopeInThread($query, string $threadId)
    {
        return $query->where('thread_id', $threadId);
    }

    /**
     * Scope for messages involving a user (sent or received)
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('recipient_id', $userId);
        });
    }

    /**
     * Generate thread ID for two users
     */
    public static function generateThreadId(int $userId1, int $userId2): string
    {
        $users = [$userId1, $userId2];
        sort($users);
        return 'thread_' . implode('_', $users);
    }

    /**
     * Get messages in a thread between two users
     */
    public static function getThreadMessages(int $userId1, int $userId2)
    {
        $threadId = static::generateThreadId($userId1, $userId2);
        
        return static::with(['sender', 'recipient'])
            ->inThread($threadId)
            ->orderBy('sent_at', 'asc')
            ->get();
    }

    /**
     * Send a secure message
     */
    public static function sendMessage(int $senderId, int $recipientId, string $message): self
    {
        $threadId = static::generateThreadId($senderId, $recipientId);
        
        return static::create([
            'thread_id' => $threadId,
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'message' => $message,
            'sent_at' => now()
        ]);
    }
}