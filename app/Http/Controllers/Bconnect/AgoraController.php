<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Services\AgoraService;
use Illuminate\Http\Request;
use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;

class AgoraController extends Controller {
    public function token(Request $r) {
        $r->validate(['channel' => 'required', 'uid' => 'required|integer']);
        $token = AgoraService::generateRtcToken($r->channel, (int) $r->uid, RtcTokenBuilder::RolePublisher, 3600);
        if (!$token) {
            return response()->json(['error' => 'Agora not configured. Add App ID and Certificate in Admin > Settings > Agora.'], 400);
        }
        return response()->json(['token' => $token, 'app_id' => AgoraService::getAppId(), 'uid' => (int) $r->uid, 'channel' => $r->channel]);
    }
}
