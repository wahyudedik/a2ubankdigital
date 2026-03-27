<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'user_id',
        'ticket_number',
        'subject',
        'category',
        'priority',
        'status',
        'assigned_to',
        'closed_at'
    ];

    protected $casts = [
        'closed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    /**
     * Scope for open tickets
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    /**
     * Scope for closed tickets
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'CLOSED');
    }

    /**
     * Scope for specific priority
     */
    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}