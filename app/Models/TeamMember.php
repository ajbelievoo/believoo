<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $fillable = [
        'name',
        'role',
        'category',
        'bio',
        'photo_path',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
        'order',
        'is_active',
    ];
}
