<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'session_id',
        'sender_name',
        'sender_email',
        'phone_number',
        'message',
        'attachment',
        'type',
        'admin_id',
        'agent_id',
        'is_handoff',
        'is_read',
        'admin_response',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function agent()
    {
        return $this->belongsTo(SupportAgent::class, 'agent_id');
    }

    protected $casts = [
        'is_read' => 'boolean',
        'is_handoff' => 'boolean',
    ];
}
