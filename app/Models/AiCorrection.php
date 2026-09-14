<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiCorrection extends Model {
    protected $table = 'ai_corrections';
    protected $guarded = [];
    protected $casts = ['applied' => 'boolean'];
}
