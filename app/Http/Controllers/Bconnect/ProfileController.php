<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller {
    public function show() {
        return view('bconnect.profile');
    }

    public function update(Request $r) {
        $user = $r->user();
        $r->validate([
            'name' => 'sometimes|required|string|max:120',
            'phone' => 'sometimes|nullable|string|max:30',
            'avatar' => 'sometimes|nullable|image|max:4096',
            'current_password' => 'required_with:new_password',
            'new_password' => ['sometimes', 'nullable', 'confirmed', Password::min(8)],
        ]);

        if ($r->filled('name')) $user->name = $r->name;
        if ($r->has('phone')) $user->phone = $r->phone;

        if ($r->hasFile('avatar')) {
            $path = $r->file('avatar')->storePublicly('avatars', 'public');
            $user->avatar = '/storage/' . $path;
            $user->profile_photo_path = $user->avatar;
        }

        if ($r->filled('new_password')) {
            if (!Hash::check($r->current_password, $user->password)) {
                return back()->with('error', 'Current password is wrong.');
            }
            $user->password = Hash::make($r->new_password);
        }

        $user->save();
        return back()->with('success', 'Profile updated.');
    }
}
