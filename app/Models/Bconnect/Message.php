<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class Message extends Model {
    protected $table = 'bconnect_messages';
    protected $guarded = [];
    protected $casts = ['attachments' => 'array'];
    public function member() { return $this->belongsTo(Member::class, 'member_id'); }
    public function channel() { return $this->morphTo(); }
}
