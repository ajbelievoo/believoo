<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller {
    public function index(Request $r) {
        $notifications = Notification::where('company_id', $r->input('bconnect_company_id'))
            ->where('member_id', $r->input('bconnect_member')->id)
            ->latest()->paginate(20);
        return view('bconnect.notifications', compact('notifications'));
    }

    public function markRead(Request $r, Notification $notification) {
        if ($notification->member_id != $r->input('bconnect_member')->id) abort(403);
        $notification->update(['is_read' => true]);
        return back();
    }
}
