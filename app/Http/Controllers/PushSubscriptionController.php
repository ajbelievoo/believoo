<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['subscription' => 'required']);

        $sub = $request->input('subscription');
        $userId = auth()->id();

        PushSubscription::updateOrCreate(
            ['endpoint' => $sub['endpoint']],
            [
                'user_id' => $userId,
                'session_id' => $userId ? (string) $userId : session()->getId(),
                'keys' => json_encode($sub['keys'] ?? []),
            ]
        );

        return response()->json(['success' => true]);
    }
}
