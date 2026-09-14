<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_id',
        'name',
        'email',
        'subject',
        'priority',
        'status',
        'category',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($ticket) {
            $ticket->ticket_id = 'TKT-' . strtoupper(\Illuminate\Support\Str::random(8));
        });
    }
}
