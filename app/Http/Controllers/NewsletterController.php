<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterSubscribed;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));

        $subscriber = NewsletterSubscriber::firstOrNew(['email' => $email]);
        $isNew = !$subscriber->exists || !$subscriber->is_active;

        $subscriber->is_active = true;
        $subscriber->save();

        if ($isNew) {
            try {
                Mail::to($subscriber->email)->send(new NewsletterSubscribed($subscriber));
            } catch (\Throwable $e) {
                Log::warning('Newsletter confirmation email failed: ' . $e->getMessage());
            }

            return redirect()->back()->with('newsletter_success', 'Thank you! You are now subscribed to our updates.');
        }

        return redirect()->back()->with('newsletter_success', 'You are already subscribed — thank you!');
    }

    public function unsubscribe(string $token)
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->firstOrFail();
        $subscriber->update(['is_active' => false]);

        return view('newsletter-unsubscribed', ['email' => $subscriber->email]);
    }
}
