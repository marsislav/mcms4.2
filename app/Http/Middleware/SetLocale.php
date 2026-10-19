<?php

namespace App\Http\Middleware;

use App\Support\AdminLang;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cookie;
use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $default = mcms_default_locale();
        $locale = $default;

        $route = $request->route();
        if ($route && $route->hasParameter('locale')) {
            $candidate = (string) $route->parameter('locale');
            if (! in_array($candidate, mcms_locales(), true) || $candidate === $default) {
                abort(404);
            }
            $locale = $candidate;
            $route->forgetParameter('locale'); // controllers never see it
        }

        // admin panel and installer: English by default, Bulgarian on request (?lang=bg, remembered in a cookie)
        if ($request->is(config('mcms.admin_path'), config('mcms.admin_path').'/*', 'install', 'install/*')) {
            $query = $request->query('lang');
            $locale = AdminLang::pick(is_string($query) ? $query : null, (string) $request->cookie(AdminLang::COOKIE), config('mcms.admin_locale'));
            if (AdminLang::valid(is_string($query) ? $query : null)) {
                Cookie::queue(Cookie::make(AdminLang::COOKIE, $locale, 60 * 24 * 365, null, null, null, true, false, 'lax'));
            }
        }

        app()->setLocale($locale);
        Carbon::setLocale(substr($locale, 0, 2));

        return $next($request);
    }
}
