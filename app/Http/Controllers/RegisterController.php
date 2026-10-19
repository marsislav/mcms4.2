<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Members;
use App\Support\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/** Public self-registration (Settings > Потребители). */
class RegisterController extends Controller
{
    public function show()
    {
        abort_unless(Members::registrationOpen(), 404);
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('site.register', ['ts' => time()]);
    }

    public function store(Request $request)
    {
        abort_unless(Members::registrationOpen(), 404);

        // honeypot + a form that was "filled" in under 3 seconds = bot
        if ($request->filled('website') || time() - (int) $request->input('ts') < 3) {
            return redirect()->route('register')->with('reg_ok', t('register.done', __('Благодарим! Профилът е създаден.')));
        }

        $key = 'register:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput($request->only('name', 'email'))->with('reg_error', t('register.throttle', __('Твърде много опити. Опитайте по-късно.')));
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email:rfc|max:190|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $approval = Options::on('reg_approval');
        $user = new User;
        $user->name = trim(strip_tags($data['name']));
        $user->email = mb_strtolower(trim($data['email']));
        $user->password = $data['password'];
        $user->role = Members::defaultRole();
        $user->active = ! $approval;
        $user->username = Members::usernameFrom($user->name, fn ($u) => User::where('username', $u)->exists());
        $user->password_changed_at = now();
        $user->save();

        audit('register', $user->email, ['role' => $user->role, 'approval' => $approval]);
        do_action('user.registered', $user);
        $this->notifyAdmin($user, $approval);

        if ($approval) {
            return redirect()->route('register')->with('reg_ok', t('register.pending', __('Профилът е създаден и чака одобрение от администратор.')));
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put([
            'mcms_ua' => hash('sha256', (string) $request->userAgent()),
            'mcms_last_activity' => time(),
        ]);

        return redirect()->route('admin.profile')->with('status', __('Добре дошли! Попълнете резюмето си.'));
    }

    private function notifyAdmin(User $user, bool $approval): void
    {
        if (! Options::on('reg_notify')) {
            return;
        }
        $to = setting('contact_email') ?: User::where('role', 'admin')->where('active', true)->value('email');
        if (! $to) {
            return;
        }
        try {
            Mail::raw(__('Нова регистрация: :name <:email> (:role)', ['name' => $user->name, 'email' => $user->email, 'role' => $user->roleLabel()]).($approval ? "\n".__('Чака одобрение: :url', ['url' => admin_url('users')]) : ''), function ($m) use ($to) {
                $m->to($to)->subject('['.setting('site_name', 'mCMS').'] '.__('Нова регистрация'));
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
