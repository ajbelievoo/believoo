<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ChatNote extends Model {
    protected $table = 'chat_notes';
    protected $guarded = [];
    public function agent() {
        return $this->belongsTo(SupportAgent::class, 'agent_id');
    }
}
