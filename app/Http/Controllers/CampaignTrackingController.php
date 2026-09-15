<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\EmailCampaign;
use Illuminate\Http\Request;

class CampaignTrackingController extends Controller
{
    public function pixel(string $token)
    {
        $recipient = CampaignRecipient::where('open_token', $token)->first();

        if ($recipient) {
            $recipient->update(['status' => 'opened', 'opened_at' => now()]);
            if ($recipient->campaign) {
                $recipient->campaign->increment('opened_count');
            }
        }

        return response(
            base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'),
            200,
            ['Content-Type' => 'image/gif']
        );
    }

    public function click(Request $request, string $token)
    {
        $recipient = CampaignRecipient::where('click_token', $token)->first();

        if ($recipient) {
            $recipient->update(['status' => 'clicked', 'clicked_at' => now()]);
            if ($recipient->campaign) {
                $recipient->campaign->increment('clicked_count');
            }
        }

        $url = $request->input('url', '/');
        return redirect()->away($url);
    }
}
