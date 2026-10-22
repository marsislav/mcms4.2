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
        return view('admin.profile', ['user' => $request->user(), 'members' => \App\Support\Members::ready()]);
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

        return back()->with('status', __('Профилът е обновен.'));
    }

    /** "Résumé": public bio shown on the author page, plus website. */
    public function updateBio(Request $request)
    {
        abort_unless(\App\Support\Members::ready(), 404);
        $user = $request->user();
        $data = $request->validate([
            'bio' => 'nullable|string|max:30000',
            'website' => 'nullable|url|max:190',
        ]);

        $user->forceFill([
            'bio' => ($b = trim(\App\Support\Sanitizer::clean((string) ($data['bio'] ?? '')))) === '' ? null : $b,
            'website' => preg_match('~^https?://~i', (string) ($data['website'] ?? '')) ? $data['website'] : null,
        ])->save();
        if (empty($user->username)) {
            $user->forceFill(['username' => \App\Support\Members::usernameFrom($user->name, fn ($u) => \App\Models\User::where('username', $u)->exists())])->save();
        }
        audit('profile.bio');

        return back()->with('status', __('Резюмето е запазено.'));
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

        return back()->with('status', __('Паролата е променена. Другите сесии са прекратени.'));
    }

    private function requirePassword($user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['current_password' => __('Грешна текуща парола.')]);
        }
    }
}
