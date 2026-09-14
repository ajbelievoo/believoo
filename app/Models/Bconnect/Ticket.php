<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class Ticket extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_tickets';
    protected $casts = ['attachments' => 'array', 'ai_tags' => 'array', 'resolved_at' => 'datetime'];
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function project() { return $this->belongsTo(Project::class, 'project_id'); }
    public function reporter() { return $this->belongsTo(Member::class, 'reporter_id'); }
    public function assignee() { return $this->belongsTo(Member::class, 'assignee_id'); }
    public function comments() { return $this->hasMany(TicketComment::class, 'ticket_id'); }
}
