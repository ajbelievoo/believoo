<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    protected $fillable = [
        'session_id',
        'type',
        'message',
        'lang',
        'attachment',
    ];
}
