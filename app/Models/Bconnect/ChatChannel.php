<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;

class ChatChannel extends Model {
    protected $table = 'bconnect_chat_channels';
    protected $guarded = [];
    protected $casts = ['member_ids' => 'array', 'is_private' => 'boolean'];
    public function creator() { return $this->belongsTo(Member::class, 'created_by'); }
    public function messages() { return $this->morphMany(Message::class, 'channel'); }

    public function canView(int $memberId): bool {
        if (!$this->is_private) return true;
        if ($this->created_by === $memberId) return true;
        return in_array($memberId, $this->member_ids ?: [], true);
    }
}
