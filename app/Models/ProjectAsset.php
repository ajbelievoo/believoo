<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectAsset extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'file_path',
        'type',
        'uploaded_by',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
