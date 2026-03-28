<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'support_tickets';

    protected $fillable = [
        'user_id',
        'ticket_code',
        'subject',
        'status',
        'created_by_staff_id'
    ];

    protected $casts = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdByStaff()
    {
        return $this->belongsTo(User::class, 'created_by_staff_id');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    /**
     * Scope for open tickets
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope for closed tickets
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}