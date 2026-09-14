<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailTestController extends Controller {
    public function send(Request $r) {
        $to = $r->input('email', $r->user()->email);
        try {
            Mail::raw('This is a test email from Believoo B-CONNECT. SMTP is working.', function ($msg) use ($to) {
                $msg->to($to)->subject('Believoo SMTP Test');
            });
            return back()->with('success', 'Test email sent to ' . $to);
        } catch (\Exception $e) {
            return back()->with('error', 'SMTP failed: ' . $e->getMessage());
        }
    }
}
