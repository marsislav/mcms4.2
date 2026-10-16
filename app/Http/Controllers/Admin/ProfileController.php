<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function updateInfo(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'current_password' => 'required|string',
        ]);
        $this->requirePassword($user, $data['current_password']);

        $user->name = strip_tags($data['name']);
        $user->save();
        audit('profile.update');

        return back()->with('status', 'Профилът е обновен.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);
        $this->requirePassword($user, $data['current_password']);

        $user->password = $data['password'];
        $user->password_changed_at = now();
        $user->save();

        // Invalidate every other session of this account
        Auth::logoutOtherDevices($data['password']);
        audit('password.change');

        return back()->with('status', 'Паролата е променена. Другите сесии са прекратени.');
    }

    private function requirePassword($user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['current_password' => 'Грешна текуща парола.']);
        }
    }
}
