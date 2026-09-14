<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'message',
        'message_hi',
        'type',
        'locale',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }
}
