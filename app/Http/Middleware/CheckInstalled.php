<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckInstalled
{
    public function handle(Request $request, Closure $next)
    {
        $installed = mcms_installed();
        $isInstaller = $request->is('install', 'install/*');

        if (! $installed && ! $isInstaller && ! $request->is('up')) {
            return redirect('/install');
        }

        if ($installed && $isInstaller) {
            abort(404);
        }

        return $next($request);
    }
}
