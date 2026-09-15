<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'type',
        'message',
        'citations',
        'source',
        'lang',
        'attachment',
    ];

    protected $casts = [
        'citations' => 'array',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feedback()
    {
        return $this->hasOne(AiFeedback::class);
    }
}
