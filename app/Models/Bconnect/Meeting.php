<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class Meeting extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_meetings';
    protected $casts = ['action_items' => 'array', 'started_at' => 'datetime', 'ended_at' => 'datetime'];
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function project() { return $this->belongsTo(Project::class, 'project_id'); }
    public function creator() { return $this->belongsTo(Member::class, 'created_by'); }
}
