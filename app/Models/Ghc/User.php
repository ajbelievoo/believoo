<?php

namespace App\Models\Ghc;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $guarded = [];
    protected $table = 'users';
    protected $connection = 'ghc';
    public $timestamps = true;
    protected $keyType = 'string';
    public $incrementing = false;
}
