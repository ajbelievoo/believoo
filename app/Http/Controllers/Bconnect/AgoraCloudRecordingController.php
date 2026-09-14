<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Meeting;
use App\Services\AgoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AgoraCloudRecordingController extends Controller {
    protected function s3RegionCode($region) {
        $map = [
            'us-east-1' => 0, 'us-east-2' => 1, 'us-west-1' => 2, 'us-west-2' => 3,
            'ap-south-1' => 4, 'ap-northeast-3' => 5, 'ap-northeast-2' => 6, 'ap-southeast-1' => 7,
            'ap-southeast-2' => 8, 'ap-northeast-1' => 9, 'ca-central-1' => 10, 'cn-north-1' => 11,
            'cn-northwest-1' => 12, 'eu-central-1' => 13, 'eu-west-1' => 14, 'eu-west-2' => 15,
            'eu-west-3' => 16, 'eu-north-1' => 17, 'sa-east-1' => 18, 'me-south-1' => 19,
        ];
        if (is_numeric($region)) return (int) $region;
        return $map[strtolower($region ?? '')] ?? 0;
    }
    public function start(Request $r, $room) {
        if (!\App\Services\BconnectPlanService::canUseRecording($r->input('bconnect_company_id'))) {
            return response()->json(['error' => 'Cloud recording is available on Enterprise plan.'], 403);
        }
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $appId = AgoraService::getAppId();
        $token = AgoraService::generateRtcToken($room, 0, \TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder::RolePublisher, 3600);
        $customerId = $settings['agora_customer_id'] ?? '';
        $secret = $settings['agora_customer_secret'] ?? '';
        if (!$appId || !$customerId || !$secret) {
            return response()->json(['error' => 'Agora cloud recording requires Customer ID/Secret + App ID.'], 400);
        }
        $creds = base64_encode("{$customerId}:{$secret}");
        $channel = $room;
        $uid = 0;

        // Acquire resource
        $acq = Http::withHeaders(['Authorization' => 'Basic ' . $creds, 'Content-Type' => 'application/json'])
            ->post("https://api.agora.io/v1/apps/{$appId}/cloud_recording/acquire", [
                'cname' => $channel,
                'uid' => (string) $uid,
                'clientRequest' => ['resourceExpiredHour' => 24, 'scene' => 0]
            ]);
        if (!$acq->successful()) return response()->json(['error' => 'Agora acquire failed', 'details' => $acq->body()], 400);
        $resourceId = $acq->json('resourceId');

        // Start recording
        $start = Http::withHeaders(['Authorization' => 'Basic ' . $creds, 'Content-Type' => 'application/json'])
            ->post("https://api.agora.io/v1/apps/{$appId}/cloud_recording/resourceid/{$resourceId}/mode/mix/start", [
                'cname' => $channel,
                'uid' => (string) $uid,
                'clientRequest' => [
                    'token' => $token,
                    'recordingConfig' => ['maxIdleTime' => 30, 'streamTypes' => 2, 'channelType' => 0, 'videoStreamType' => 0, 'transcodingConfig' => ['width' => 1280, 'height' => 720, 'fps' => 15, 'bitrate' => 800, 'mixedVideoLayout' => 1]],
                    'storageConfig' => ['vendor' => 1, 'region' => $this->s3RegionCode($settings['agora_recording_region'] ?? 0), 'bucket' => $settings['agora_recording_bucket'] ?? '', 'accessKey' => $settings['agora_recording_access_key'] ?? '', 'secretKey' => $settings['agora_recording_secret_key'] ?? '', 'fileNamePrefix' => ['bconnect', $room]],
                ]
            ]);
        if (!$start->successful()) return response()->json(['error' => 'Agora start recording failed', 'details' => $start->body()], 400);

        $meeting->update(['recording_sid' => $start->json('sid'), 'recording_resource' => $resourceId, 'recording_status' => 'started']);
        return response()->json(['success' => true, 'sid' => $start->json('sid'), 'resourceId' => $resourceId]);
    }

    public function stop(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        if (!$meeting->recording_sid || !$meeting->recording_resource) return response()->json(['error' => 'No active recording'], 400);
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $appId = AgoraService::getAppId();
        $customerId = $settings['agora_customer_id'] ?? '';
        $secret = $settings['agora_customer_secret'] ?? '';
        $creds = base64_encode("{$customerId}:{$secret}");
        $resp = Http::withHeaders(['Authorization' => 'Basic ' . $creds, 'Content-Type' => 'application/json'])
            ->post("https://api.agora.io/v1/apps/{$appId}/cloud_recording/resourceid/{$meeting->recording_resource}/sid/{$meeting->recording_sid}/mode/mix/stop", [
                'cname' => $room,
                'uid' => '0',
                'clientRequest' => (object)[]
            ]);
        $meeting->update(['recording_status' => 'stopped']);
        return response()->json(['success' => true, 'data' => $resp->json()]);
    }
}
