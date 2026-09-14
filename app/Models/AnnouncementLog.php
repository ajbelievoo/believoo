<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'email',
        'product',
        'level',
        'message',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }
}
