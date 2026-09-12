<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'email',
        'product',
        'variant',
        'is_test',
        'sent_at',
        'opened_at',
        'clicked_at',
        'click_url',
    ];

    protected $casts = [
        'is_test' => 'boolean',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }
}
