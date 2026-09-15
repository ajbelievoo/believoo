<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class Meeting extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_meetings';
    protected $casts = ['action_items' => 'array', 'started_at' => 'datetime', 'ended_at' => 'datetime', 'scheduled_at' => 'datetime'];
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function project() { return $this->belongsTo(Project::class, 'project_id'); }
    public function creator() { return $this->belongsTo(Member::class, 'created_by'); }
    public function transcripts() { return $this->hasMany(MeetingTranscript::class, 'meeting_id')->orderBy('starts_at'); }
    public function notes() { return $this->hasMany(MeetingNote::class, 'meeting_id')->orderBy('created_at', 'desc'); }
    public function latestNote() { return $this->hasOne(MeetingNote::class, 'meeting_id')->latest('created_at'); }
}
