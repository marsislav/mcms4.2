<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Privacy-friendly visit counter: one row per day and counted thing in `page_views` (no IPs, no cookies).
 * Keys: c:<content id> | cat:<id> | tag:<id> | p:<name> (blog, home, search, contact, author:<username>).
 * Settings: Settings > Statistics.
 */
class Stats
{
    private const BOT = '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|monitor|uptime|curl|wget|python-requests|httpclient|lighthouse|pingdom|feedfetcher|mediapartners/i';

    public static function isBot(?string $userAgent): bool
    {
        return $userAgent === null || $userAgent === '' || (bool) preg_match(self::BOT, $userAgent);
    }

    /** Counts one visit (silently does nothing when disabled, unavailable or for excluded visitors). */
    public static function hit(string $key, ?string $label = null, ?int $contentId = null): void
    {
        try {
            if (! Options::on('stats_on') || ! Tables::has('page_views')) {
                return;
            }
            $req = request();
            if (! $req->isMethod('GET') || $req->ajax()) {
                return;
            }
            if (Options::on('stats_skip_bots') && self::isBot($req->userAgent())) {
                return;
            }
            if (Options::on('stats_skip_logged') && auth()->check()) {
                return;
            }
            DB::table('page_views')->upsert(
                [['day' => now()->toDateString(), 'view_key' => mb_substr($key, 0, 120), 'content_id' => $contentId, 'label' => $label ? mb_substr($label, 0, 190) : null, 'hits' => 1]],
                ['day', 'view_key'],
                ['hits' => DB::raw('hits + 1'), 'label' => $label ? mb_substr($label, 0, 190) : DB::raw('label')]
            );
            // occasional cleanup (about 1 in 200 requests)
            if (random_int(1, 200) === 1) {
                DB::table('page_views')->where('day', '<', now()->subDays((int) Options::get('stats_keep_days'))->toDateString())->delete();
            }
        } catch (\Throwable) {
            // statistics must never break a page
        }
    }

    /** Most read posts: [content_id => hits]. $days = 0 means all time. */
    public static function popularIds(int $limit, ?int $days = null): array
    {
        if (! Tables::has('page_views')) {
            return [];
        }
        $days ??= (int) Options::get('popular_days');
        $q = DB::table('page_views')->whereNotNull('content_id')->selectRaw('content_id, SUM(hits) as total')->groupBy('content_id')->orderByDesc('total')->limit($limit * 3);
        if ($days > 0) {
            $q->where('day', '>=', now()->subDays($days)->toDateString());
        }

        return $q->pluck('total', 'content_id')->all();
    }

    /** Totals for the dashboard: today / last 7 days / last 30 days, plus the top counted things. */
    public static function summary(): array
    {
        if (! Tables::has('page_views')) {
            return [];
        }
        $sum = fn ($from) => (int) DB::table('page_views')->where('day', '>=', $from)->sum('hits');

        return [
            'today' => $sum(now()->toDateString()),
            'week' => $sum(now()->subDays(6)->toDateString()),
            'month' => $sum(now()->subDays(29)->toDateString()),
            'top' => DB::table('page_views')->where('day', '>=', now()->subDays(29)->toDateString())
                ->selectRaw('view_key, MAX(label) as label, SUM(hits) as total')->groupBy('view_key')->orderByDesc('total')->limit(10)->get(),
            'daily' => DB::table('page_views')->where('day', '>=', now()->subDays(29)->toDateString())
                ->selectRaw('day, SUM(hits) as total')->groupBy('day')->orderBy('day')->pluck('total', 'day')->all(),
        ];
    }
}
