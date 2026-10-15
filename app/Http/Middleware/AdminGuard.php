<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Protects the admin panel: authentication, active account, idle timeout,
 * session/user-agent binding, role permission and optional mandatory 2FA.
 * Usage: 'admin' or 'admin:permission'
 */
class AdminGuard
{
    public function handle(Request $request, Closure $next, ?string $permission = null)
    {
        $user = Auth::user();

        if (! $user || ! $user->active) {
            return $this->kick($request, $user ? 'Профилът е деактивиран.' : null);
        }

        $session = $request->session();

        if (hash('sha256', (string) $request->userAgent()) !== $session->get('mcms_ua')) {
            return $this->kick($request, 'Сесията е невалидна. Влезте отново.');
        }

        $last = (int) $session->get('mcms_last_activity', 0);
        if ($last && time() - $last > config('mcms.idle_minutes') * 60) {
            return $this->kick($request, 'Сесията изтече поради неактивност.');
        }
        $session->put('mcms_last_activity', time());

        if ($permission && ! $user->allows($permission)) {
            abort(403);
        }

        if (setting('require_2fa_admin') === '1' && $user->isAdmin() && ! $user->totp_enabled
            && ! $request->routeIs('admin.profile*', 'admin.logout')) {
            return redirect()->route('admin.profile')
                ->with('error', 'Политиката изисква двуфакторна защита (2FA) за администратори. Активирайте я, за да продължите.');
        }

        return $next($request);
    }

    private function kick(Request $request, ?string $message)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirect = redirect()->route('admin.login');

        return $message ? $redirect->with('error', $message) : $redirect;
    }
}
