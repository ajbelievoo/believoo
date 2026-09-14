<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class TicketComment extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_ticket_comments';
    protected $casts = ['attachments' => 'array'];
    public function ticket() { return $this->belongsTo(Ticket::class, 'ticket_id'); }
    public function member() { return $this->belongsTo(Member::class, 'member_id'); }
}
