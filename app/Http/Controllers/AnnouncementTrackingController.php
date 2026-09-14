<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\EmailPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AnnouncementTrackingController extends Controller
{
    public function open(Announcement $announcement, AnnouncementRecipient $recipient)
    {
        if ($recipient->announcement_id !== $announcement->id) {
            abort(404);
        }

        if (! $recipient->opened_at) {
            $recipient->update(['opened_at' => now()]);
        }

        $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=');

        return response($pixel, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function click(Announcement $announcement, AnnouncementRecipient $recipient, Request $request)
    {
        if ($recipient->announcement_id !== $announcement->id) {
            abort(404);
        }

        $url = $request->input('url');
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(404);
        }

        $recipient->update([
            'clicked_at' => now(),
            'click_url' => $url,
        ]);

        return redirect($url);
    }

    public function unsubscribe(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        $email = $request->input('email');
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            abort(404);
        }

        $preference = EmailPreference::firstOrCreate(['email' => $email], ['announcements' => false]);
        $preference->update([
            'announcements' => false,
            'unsubscribed_at' => now(),
        ]);

        return view('emails.unsubscribed', ['email' => $email]);
    }

    public function resubscribe(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        $email = $request->input('email');
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            abort(404);
        }

        $preference = EmailPreference::firstOrCreate(['email' => $email], ['announcements' => true]);
        $preference->update([
            'announcements' => true,
            'unsubscribed_at' => null,
        ]);

        return view('emails.resubscribed', ['email' => $email]);
    }
}
