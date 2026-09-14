<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SlaBreach extends Model {
    protected $table = 'sla_breaches';
    protected $guarded = [];
    protected $casts = ['resolved' => 'boolean'];
}
