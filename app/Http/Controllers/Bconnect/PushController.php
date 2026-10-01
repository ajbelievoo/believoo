<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushController extends Controller {
    public function subscribe(Request $r) {
        $r->validate(['subscription' => 'required']);
        PushSubscription::updateOrCreate(
            ['endpoint' => $r->subscription['endpoint']],
            [
                'session_id' => (string) $r->user()->id,
                'keys' => json_encode($r->subscription['keys'] ?? []),
            ]
        );
        return response()->json(['success' => true]);
    }

    public function sendTest(Request $r) {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $public = $settings['vapid_public_key'] ?? '';
        $private = $settings['vapid_private_key'] ?? '';
        if (!$public || !$private) return response()->json(['error' => 'VAPID not configured'], 400);

        $webPush = new WebPush(['VAPID' => ['subject' => 'mailto:support@believoo.com', 'publicKey' => $public, 'privateKey' => $private]]);
        $subs = PushSubscription::all();
        foreach ($subs as $s) {
            $sub = json_decode($s->keys, true);
            $sub['endpoint'] = $s->endpoint;
            $webPush->queueNotification(
                Subscription::create($sub),
                json_encode(['title' => 'Bmydesk', 'body' => 'Test notification', 'url' => 'https://bc.believoo.com'])
            );
        }
        return response()->json(['success' => true, 'sent' => $subs->count()]);
    }
}
