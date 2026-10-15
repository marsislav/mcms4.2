<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** Valid bcrypt hash used to keep response time constant for unknown e-mails. */
    private const DUMMY_HASH = '$2y$12$rCSo8UgHKtNRFz3eBXmUAuc/QhzHIPrr7PUfl1dZ.Qs.b3tphvbaq';

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:190',
            'password' => 'required|string|max:200',
        ]);
        $email = Str::lower(trim($data['email']));

        $throttle = 'login:'.sha1($email.'|'.$request->ip());
        $ipThrottle = 'login-ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttle, 5) || RateLimiter::tooManyAttempts($ipThrottle, 20)) {
            $wait = max(RateLimiter::availableIn($throttle), RateLimiter::availableIn($ipThrottle));

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "Твърде много опити. Опитайте отново след {$wait} сек."]);
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $valid = password_verify($data['password'], $user->password);
        } else {
            password_verify($data['password'], self::DUMMY_HASH);
            $valid = false;
        }

        if (! $valid || ! $user->active || $user->isLocked()) {
            RateLimiter::hit($throttle, 600);
            RateLimiter::hit($ipThrottle, 600);

            if ($user && ! $valid) {
                $user->failed_attempts++;
                if ($user->failed_attempts >= 8) {
                    $user->locked_until = now()->addMinutes(15);
                    $user->failed_attempts = 0;
                    audit('login.locked', $user->email);
                }
                $user->save();
            }
            audit('login.failed', $email);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Невалидни данни за вход.']);
        }

        RateLimiter::clear($throttle);

        if ($user->totp_enabled) {
            $request->session()->regenerate();
            $request->session()->put('mcms_2fa', ['id' => $user->id, 'at' => time(), 'tries' => 0]);

            return redirect()->route('admin.challenge');
        }

        return $this->completeLogin($request, $user);
    }

    public function showChallenge(Request $request)
    {
        return $this->pending($request) ? view('admin.challenge') : redirect()->route('admin.login');
    }

    public function challenge(Request $request)
    {
        $pending = $this->pending($request);
        if (! $pending) {
            return redirect()->route('admin.login')->with('error', 'Времето за потвърждение изтече.');
        }

        $request->validate(['code' => 'required|string|max:32']);
        $user = User::find($pending['id']);

        $key = 'twofa:'.$request->ip();
        if (! $user || RateLimiter::tooManyAttempts($key, 10)) {
            $request->session()->forget('mcms_2fa');

            return redirect()->route('admin.login')->with('error', 'Твърде много опити.');
        }

        $code = trim($request->input('code'));
        $ok = false;

        if (preg_match('/^\d{3}\s?\d{3}$/', $code)) {
            $step = Totp::verify((string) $user->totp_secret, $code);
            if ($step !== null && $step > (int) $user->totp_last_step) {
                $user->totp_last_step = $step; // blocks code replay
                $ok = true;
            }
        } else {
            $ok = $this->useRecoveryCode($user, $code);
        }

        if (! $ok) {
            RateLimiter::hit($key, 600);
            $pending['tries']++;
            audit('2fa.failed', $user->email);
            if ($pending['tries'] >= 5) {
                $request->session()->forget('mcms_2fa');

                return redirect()->route('admin.login')->with('error', 'Твърде много грешни кодове. Влезте отново.');
            }
            $request->session()->put('mcms_2fa', $pending);

            return back()->withErrors(['code' => 'Невалиден код.']);
        }

        $user->save();
        $request->session()->forget('mcms_2fa');

        return $this->completeLogin($request, $user);
    }

    public function logout(Request $request)
    {
        audit('logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Излязохте успешно.');
    }

    private function pending(Request $request): ?array
    {
        $p = $request->session()->get('mcms_2fa');

        return (is_array($p) && time() - $p['at'] < 300) ? $p : null;
    }

    private function completeLogin(Request $request, User $user)
    {
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put([
            'mcms_ua' => hash('sha256', (string) $request->userAgent()),
            'mcms_last_activity' => time(),
        ]);

        $user->forceFill([
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        audit('login', $user->email);
        do_action('user.login', $user);

        return redirect()->intended(route('admin.dashboard'));
    }

    private function useRecoveryCode(User $user, string $input): bool
    {
        $input = strtolower(preg_replace('/[^a-z0-9]/i', '', $input));
        if (strlen($input) !== 10) {
            return false;
        }
        $hash = self::hashRecovery($input);
        $codes = $user->recovery_codes ?? [];
        foreach ($codes as $i => $stored) {
            if (hash_equals($stored, $hash)) {
                unset($codes[$i]);
                $user->recovery_codes = array_values($codes);
                audit('2fa.recovery_used', $user->email);

                return true;
            }
        }

        return false;
    }

    public static function hashRecovery(string $plain): string
    {
        return hash_hmac('sha256', strtolower(preg_replace('/[^a-z0-9]/i', '', $plain)), (string) config('app.key'));
    }
}
