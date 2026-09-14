<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class BconnectFile extends Model {
    protected $table = 'bconnect_files';
    protected $guarded = [];
    public function member() { return $this->belongsTo(Member::class, 'member_id'); }
    public function project() { return $this->belongsTo(Project::class, 'project_id'); }
}
