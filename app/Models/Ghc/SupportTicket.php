<?php

namespace App\Models\Ghc;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $guarded = [];
    protected $table = 'support_tickets';
    protected $connection = 'ghc';
    public $timestamps = true;
    protected $keyType = 'string';
    public $incrementing = false;

    public function replies()
    {
        return $this->hasMany(SupportReply::class, 'ticket_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getMessageAttribute(): ?string
    {
        $reply = $this->replies()->where('sender', 'user')->orderBy('created_at', 'asc')->first();
        return $reply?->message;
    }
}
