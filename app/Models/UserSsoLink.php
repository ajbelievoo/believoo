<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSsoLink extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'attributes' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function provider()
    {
        return $this->belongsTo(SsoProvider::class, 'sso_provider_id');
    }
}
