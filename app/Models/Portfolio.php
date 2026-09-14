<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'problem',
        'solution',
        'result',
        'image',
        'tech_stack',
        'url',
        'is_visible',
        'icon',
        'status',
        'gradient_from',
        'gradient_to',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'tech_stack' => 'array',
    ];

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
