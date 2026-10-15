<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
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

        app()->setLocale($locale);
        Carbon::setLocale(substr($locale, 0, 2));

        return $next($request);
    }
}
