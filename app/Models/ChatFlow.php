<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ChatFlow extends Model {
    protected $table = 'chat_flows';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
