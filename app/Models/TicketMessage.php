<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $table = 'support_ticket_replies';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message'
    ];

    protected $casts = [];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}