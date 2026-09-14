<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class Whiteboard extends Model {
    protected $table = 'bconnect_whiteboards';
    protected $guarded = [];
    protected $casts = ['data' => 'array'];
    public function project() { return $this->belongsTo(Project::class, 'project_id'); }
}
