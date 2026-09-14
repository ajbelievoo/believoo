<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFeedback extends Model
{
    protected $table = 'project_feedback';

    protected $fillable = [
        'agreement_id',
        'client_id',
        'rating',
        'review',
        'nps_score',
        'would_recommend',
        'categories',
        'is_approved',
        'is_featured',
        'approved_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'nps_score' => 'integer',
        'would_recommend' => 'boolean',
        'categories' => 'array',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function getRatingStarsAttribute(): string
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $this->rating ? '★' : '☆';
        }
        return $stars;
    }

    public function getNpsLabelAttribute(): string
    {
        if ($this->nps_score === null) return 'N/A';
        if ($this->nps_score >= 9) return 'Promoter';
        if ($this->nps_score >= 7) return 'Passive';
        return 'Detractor';
    }

    public function approve(): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);
    }

    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }
}
