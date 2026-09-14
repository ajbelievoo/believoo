<?php

namespace App\Models\Ghc;

use Illuminate\Database\Eloquent\Model;

class SupportReply extends Model
{
    protected $guarded = [];
    protected $table = 'support_ticket_replies';
    protected $connection = 'ghc';
    public $timestamps = true;
    const UPDATED_AT = null;
    protected $keyType = 'string';
    public $incrementing = false;

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id', 'id');
    }
}
