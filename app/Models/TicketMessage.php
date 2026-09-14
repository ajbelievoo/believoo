<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'sender_type',
        'sender_name',
        'message',
        'attachment',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
