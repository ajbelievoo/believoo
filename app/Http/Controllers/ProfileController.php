<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Use dark theme for clients, default theme for admins
        if ($user->isAdmin()) {
            return view('profile.edit', [
                'user' => $user,
            ]);
        }

        return view('profile.edit-client', [
            'user' => $user,
        ]);
    }

    /**
     * Display the complete profile form for social login users.
     */
    public function complete(Request $request): View
    {
        return view('auth.complete-profile', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Handle the complete profile form submission.
     */
    public function completeStore(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:15', Rule::unique(User::class)->ignore($user->id)],
            'locale' => ['nullable', 'string', 'in:en,hi'],
        ]);

        $user->fill($validated);
        $user->save();

        return redirect()->route('client.dashboard')->with('status', 'profile-completed');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
