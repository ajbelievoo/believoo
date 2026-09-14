<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiFeedback extends Model
{
    protected $fillable = [
        'ai_message_id',
        'rating',
        'comment',
    ];

    public function aiMessage()
    {
        return $this->belongsTo(AiMessage::class);
    }
}
