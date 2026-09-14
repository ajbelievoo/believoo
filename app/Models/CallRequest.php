<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallRequest extends Model
{
    protected $fillable = [
        'phone_number',
        'status',
        'notes',
    ];
}
