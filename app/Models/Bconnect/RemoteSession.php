<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class RemoteSession extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_remote_sessions';
    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime', 'expires_at' => 'datetime', 'viewer_joined_at' => 'datetime'];
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function requester() { return $this->belongsTo(Member::class, 'requested_by'); }
    public function target() { return $this->belongsTo(Member::class, 'target_id'); }
    public function viewer() { return $this->belongsTo(Member::class, 'viewer_member_id'); }
}
