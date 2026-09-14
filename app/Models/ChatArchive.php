<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ChatArchive extends Model {
    protected $table = 'chat_archive';
    protected $guarded = [];
    protected $casts = ['rating' => 'integer'];
}
