<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'status',
        'progress',
        'start_date',
        'end_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agreement()
    {
        return $this->belongsTo(Agreement::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function assets()
    {
        return $this->hasMany(ProjectAsset::class);
    }
}
