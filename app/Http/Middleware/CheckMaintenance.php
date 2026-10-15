<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckMaintenance
{
    public function handle(Request $request, Closure $next)
    {
        if (setting('maintenance_mode') === '1'
            && ! $request->is(config('mcms.admin_path'), config('mcms.admin_path').'/*')
            && ! (Auth::check() && Auth::user()->active)) {
            return response()->view('errors.503', [], 503)->header('Retry-After', '3600');
        }

        return $next($request);
    }
}
