<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 404 journal: one row per missing path with a hit counter, so the admin can turn frequent misses into redirects
 * (Settings > SEO & navigation > 404). Needs the `not_found_log` table; silently does nothing without it.
 */
class NotFoundLog
{
    /** Whether a path is worth logging: GET, not the admin area, no obvious scanner noise, sane length. */
    public static function loggable(string $path, string $method, string $adminPath): bool
    {
        if ($method !== 'GET' || strlen($path) > 300) {
            return false;
        }
        $p = ltrim($path, '/');
        if ($p === $adminPath || str_starts_with($p, $adminPath.'/')) {
            return false;
        }

        return ! preg_match('~\.(php\d?|asp|aspx|jsp|cgi|env|git|sql|bak|zip|ini)(/|$)|wp-(admin|login|content|includes)|xmlrpc|\.well-known~i', $p);
    }

    public static function record(Request $request): void
    {
        try {
            if (! Options::on('notfound_log') || ! Tables::has('not_found_log')) {
                return;
            }
            $path = '/'.trim($request->path(), '/');
            if (! self::loggable($path, $request->method(), (string) config('mcms.admin_path'))) {
                return;
            }
            $ref = $request->headers->get('referer');
            $now = now();
            DB::table('not_found_log')->upsert(
                [['path_hash' => md5($path), 'path' => $path, 'referrer' => $ref ? mb_substr($ref, 0, 300) : null, 'hits' => 1, 'first_seen' => $now, 'last_seen' => $now]],
                ['path_hash'],
                ['hits' => DB::raw('hits + 1'), 'last_seen' => $now, 'referrer' => $ref ? mb_substr($ref, 0, 300) : DB::raw('referrer')]
            );
            if (random_int(1, 100) === 1) {
                DB::table('not_found_log')->where('last_seen', '<', now()->subDays((int) Options::get('notfound_keep_days')))->delete();
            }
        } catch (\Throwable) {
            // the journal must never turn a 404 into a 500
        }
    }
}
