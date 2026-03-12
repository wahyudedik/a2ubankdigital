<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $table = 'ticket_messages';

    protected $fillable = [
        'ticket_id',
        'sender_id',
        'message',
        'is_from_customer'
    ];

    protected $casts = [
        'is_from_customer' => 'boolean'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Scope for customer messages
     */
    public function scopeFromCustomer($query)
    {
        return $query->where('is_from_customer', true);
    }

    /**
     * Scope for staff messages
     */
    public function scopeFromStaff($query)
    {
        return $query->where('is_from_customer', false);
    }
}