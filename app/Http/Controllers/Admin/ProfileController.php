<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $pending = $request->session()->get('mcms_totp_pending');
        $user = $request->user();

        return view('admin.profile', [
            'user' => $user,
            'secret' => $pending,
            'otpauth' => $pending ? Totp::uri($pending, $user->email, setting('site_name', config('app.name'))) : null,
            'recovery' => $request->session()->pull('mcms_new_recovery'),
        ]);
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

    public function startTwoFactor(Request $request)
    {
        $request->session()->put('mcms_totp_pending', Totp::generateSecret());

        return redirect()->route('admin.profile');
    }

    public function confirmTwoFactor(Request $request)
    {
        $user = $request->user();
        $secret = $request->session()->get('mcms_totp_pending');
        $data = $request->validate(['code' => 'required|string|max:10', 'current_password' => 'required|string']);
        $this->requirePassword($user, $data['current_password']);

        $step = $secret ? Totp::verify($secret, $data['code']) : null;
        if ($step === null) {
            return back()->withErrors(['code' => 'Невалиден код. Проверете часа на телефона си.']);
        }

        $plain = $this->newRecoveryCodes();
        $user->totp_secret = $secret;
        $user->totp_enabled = true;
        $user->totp_last_step = $step;
        $user->recovery_codes = array_map([AuthController::class, 'hashRecovery'], $plain);
        $user->save();

        $request->session()->forget('mcms_totp_pending');
        $request->session()->put('mcms_new_recovery', $plain);
        audit('2fa.enabled');

        return redirect()->route('admin.profile')->with('status', '2FA е активирана. Запазете кодовете за възстановяване!');
    }

    public function regenerateRecovery(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['current_password' => 'required|string']);
        $this->requirePassword($user, $data['current_password']);
        abort_unless($user->totp_enabled, 404);

        $plain = $this->newRecoveryCodes();
        $user->recovery_codes = array_map([AuthController::class, 'hashRecovery'], $plain);
        $user->save();
        $request->session()->put('mcms_new_recovery', $plain);
        audit('2fa.recovery_regenerated');

        return redirect()->route('admin.profile');
    }

    public function disableTwoFactor(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['current_password' => 'required|string', 'code' => 'required|string|max:10']);
        $this->requirePassword($user, $data['current_password']);

        if (! $user->totp_enabled || Totp::verify((string) $user->totp_secret, $data['code']) === null) {
            return back()->withErrors(['code' => 'Невалиден код.']);
        }

        $user->forceFill(['totp_enabled' => false, 'totp_secret' => null, 'recovery_codes' => null, 'totp_last_step' => 0])->save();
        audit('2fa.disabled');

        return back()->with('status', '2FA е изключена.');
    }

    private function requirePassword($user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['current_password' => 'Грешна текуща парола.']);
        }
    }

    /** @return string[] 8 codes formatted xxxxx-xxxxx */
    private function newRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $raw = strtolower(Str::random(10));
            $codes[] = substr($raw, 0, 5).'-'.substr($raw, 5);
        }

        return $codes;
    }
}
