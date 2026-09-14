<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageContent extends Model
{
    protected $fillable = [
        'page_name',
        'section_name',
        'key',
        'value',
        'type',
    ];

    public static function getContent($page, $key, $default = null)
    {
        $content = self::where('page_name', $page)->where('key', $key)->first();
        if (!$content) return $default;
        
        if ($content->type === 'json') {
            return json_decode($content->value, true) ?: $default;
        }
        
        return $content->value ?: $default;
    }
}
