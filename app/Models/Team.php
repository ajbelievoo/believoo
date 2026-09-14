<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Team extends Model
{
    protected $fillable = [
        'name',
        'role',
        'photo',
        'bio',
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return Storage::disk('public')->url($this->photo);
        }
        return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . "&color=7F9CF5&background=EBF4FF";
    }
}
