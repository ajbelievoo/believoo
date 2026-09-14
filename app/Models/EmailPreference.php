<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'announcements',
        'unsubscribed_at',
    ];

    protected $casts = [
        'announcements' => 'boolean',
        'unsubscribed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isUnsubscribed(): bool
    {
        return ! $this->announcements || $this->unsubscribed_at !== null;
    }
}
