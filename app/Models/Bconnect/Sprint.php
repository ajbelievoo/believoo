<?php

namespace App\Models\Bconnect;

use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    protected $guarded = [];
    protected $table = 'bconnect_sprints';

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'sprint_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getProgressAttribute(): array
    {
        $total = $this->tickets()->count();
        $done = $this->tickets()->whereIn('status', ['resolved', 'closed'])->count();

        return [
            'total' => $total,
            'done' => $done,
            'percent' => $total > 0 ? round(($done / $total) * 100, 2) : 0,
        ];
    }
}
