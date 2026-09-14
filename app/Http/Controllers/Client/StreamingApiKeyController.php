<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\StreamingApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StreamingApiKeyController extends Controller
{
    /**
     * Return a single credential for the authenticated user's own API key.
     * This avoids embedding secrets in the initial HTML/JS.
     */
    public function credential(Request $request, StreamingApiKey $apiKey, string $field)
    {
        if ($apiKey->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $allowed = ['app_certificate', 'rest_api_key'];

        if (!in_array($field, $allowed, true)) {
            return response()->json(['error' => 'Invalid field'], 400);
        }

        $value = $apiKey->{$field};

        if (!$value) {
            return response()->json(['error' => 'Not configured'], 404);
        }

        return response()->json(['success' => true, 'value' => $value, 'field' => $field]);
    }
}
