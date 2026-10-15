<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp_nonce', $nonce);

        $response = $next($request);
        $h = $response->headers;

        $csp = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self'",
            "style-src-attr 'unsafe-inline'", // style="" from the rich-text editor (values allow-listed by Sanitizer)
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            'frame-src '.implode(' ', array_map(fn ($d) => 'https://'.$d, config('mcms.embed_hosts', []))),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];
        if ($request->isSecure()) {
            $csp[] = 'upgrade-insecure-requests';
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $h->set('Content-Security-Policy', implode('; ', $csp));
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->is(config('mcms.admin_path'), config('mcms.admin_path').'/*', 'install', 'install/*')) {
            $h->set('Cache-Control', 'no-store, private');
            $h->set('X-Robots-Tag', 'noindex, nofollow');
        }

        header_remove('X-Powered-By');

        return $response;
    }
}
