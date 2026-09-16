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

    public function getTotalEstimatedHoursAttribute(): float
    {
        return (float) $this->tickets()->sum('estimated_hours') ?? 0;
    }

    public function getTotalLoggedHoursAttribute(): float
    {
        return round($this->tickets()->with('timeEntries')->get()->sum(fn ($t) => $t->timeEntries->sum('duration_seconds')) / 3600, 2);
    }

    public function getBurndownDataAttribute(): array
    {
        $total = $this->tickets()->count();
        if ($total === 0) {
            return ['labels' => [], 'ideal' => [], 'actual' => []];
        }

        $start = $this->start_date->copy()->startOfDay();
        $end = $this->end_date->copy()->endOfDay();
        $today = now()->endOfDay()->min($end);

        $days = collect();
        $current = $start->copy();
        while ($current <= $today) {
            $days->push($current->copy());
            $current->addDay();
        }

        $durationDays = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()));

        $labels = [];
        $ideal = [];
        $actual = [];

        foreach ($days as $day) {
            $labels[] = $day->format('M d');

            $dayIndex = $start->copy()->startOfDay()->diffInDays($day->copy()->startOfDay(), false);
            $idealRemaining = max(0, $total * (1 - ($dayIndex / $durationDays)));
            $ideal[] = round($idealRemaining, 2);

            $created = $this->tickets()->whereDate('created_at', '<=', $day)->count();
            $resolved = $this->tickets()->whereIn('status', ['resolved', 'closed'])
                ->where(function ($q) use ($day) {
                    $q->whereDate('resolved_at', '<=', $day)
                      ->orWhereDate('updated_at', '<=', $day);
                })
                ->count();
            $actual[] = max(0, $created - $resolved);
        }

        return ['labels' => $labels, 'ideal' => $ideal, 'actual' => $actual];
    }
}
