<?php

namespace App\Models\Bconnect;

use Illuminate\Database\Eloquent\Model;

class Message extends Model {
    protected $table = 'bconnect_messages';
    protected $guarded = [];
    protected $casts = [
        'attachments' => 'array',
        'mentions' => 'array',
        'read_by' => 'array',
    ];

    public function member() { return $this->belongsTo(Member::class, 'member_id'); }
    public function channel() { return $this->morphTo(); }
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function replies() { return $this->hasMany(self::class, 'parent_id')->oldest(); }

    public function markReadBy(int $memberId): void
    {
        $read = $this->read_by ?: [];
        if (!in_array($memberId, $read, true)) {
            $read[] = $memberId;
            $this->update(['read_by' => $read]);
        }
    }

    public function isReadBy(int $memberId): bool
    {
        return in_array($memberId, $this->read_by ?: [], true);
    }
}
