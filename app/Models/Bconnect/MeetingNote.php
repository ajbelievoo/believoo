<?php

namespace App\Models\Bconnect;

use Illuminate\Database\Eloquent\Model;

class MeetingNote extends Model
{
    protected $guarded = [];
    protected $table = 'bconnect_meeting_notes';

    protected $casts = [
        'key_points' => 'array',
        'action_items' => 'array',
        'decisions' => 'array',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }
}
