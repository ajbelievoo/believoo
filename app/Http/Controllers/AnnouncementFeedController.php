<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AnnouncementFeedController extends Controller
{
    public function rss()
    {
        $announcements = Announcement::published()->whereNotNull('sent_at')->latest('sent_at')->take(50)->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<rss version="2.0"><channel>';
        $xml .= '<title>Believoo Announcements</title>';
        $xml .= '<link>' . config('app.url') . '</link>';
        $xml .= '<description>Latest updates from Believoo</description>';

        foreach ($announcements as $a) {
            $xml .= '<item>';
            $xml .= '<title>' . e($a->title) . '</title>';
            $xml .= '<link>' . config('app.url') . '</link>';
            $xml .= '<description><![CDATA[' . $a->message . ']]></description>';
            $xml .= '<pubDate>' . $a->sent_at?->toRfc2822String() . '</pubDate>';
            $xml .= '</item>';
        }

        $xml .= '</channel></rss>';

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    public function json(Request $request)
    {
        $announcements = Announcement::published()->whereNotNull('sent_at')->latest('sent_at')->take(50)->get();

        return response()->json([
            'announcements' => $announcements->map(fn($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'message' => $a->message,
                'type' => $a->type,
                'sent_at' => $a->sent_at,
            ]),
        ]);
    }
}
