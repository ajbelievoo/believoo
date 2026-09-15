<?php

namespace App\Models\Bconnect;

use Illuminate\Database\Eloquent\Model;

class MeetingTranscript extends Model
{
    protected $guarded = [];
    protected $table = 'bconnect_meeting_transcripts';

    protected $casts = [
        'starts_at' => 'float',
        'duration' => 'float',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }
}
