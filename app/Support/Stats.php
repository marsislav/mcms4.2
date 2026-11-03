<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Visit counter: one row per day and counted thing in `page_views` (aggregate, no cookies) plus an optional
 * detailed log in `visits` (IP, browser, OS, device, referrer, country, traffic source, UTM tags, bots ...).
 * Extras: `search_terms` (what visitors search for) and `click_log` (tracked clicks).
 * Keys: c:<content id> | cat:<id> | tag:<id> | p:<name> (blog, home, search, contact, author:<username>).
 * Settings: Settings > Statistics. The static helpers without database access are unit-tested (tests/standalone.php).
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
                // crawlers never reach the human counters; they only go to the detailed log (bots report)
                if (Options::on('stats_log_bots')) {
                    self::log($req, $key, $label, $contentId);
                }

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
            self::log($req, $key, $label, $contentId);
            // occasional housekeeping (about 1 in 200 requests)
            if (random_int(1, 200) === 1) {
                self::cleanup();
            }
            // lazy weekly e-mail summary (checked on about 1 in 40 requests, sent at most once a day)
            if (random_int(1, 40) === 1) {
                StatsDigest::maybeSend();
            }
        } catch (\Throwable) {
            // statistics must never break a page
        }
    }

    /** Deletes data older than the retention settings. */
    public static function cleanup(): void
    {
        DB::table('page_views')->where('day', '<', now()->subDays((int) Options::get('stats_keep_days'))->toDateString())->delete();
        if (Tables::has('visits')) {
            DB::table('visits')->where('created_at', '<', now()->subDays((int) Options::get('stats_log_keep_days')))->delete();
        }
        if (Tables::has('search_terms')) {
            DB::table('search_terms')->where('day', '<', now()->subDays((int) Options::get('stats_keep_days'))->toDateString())->delete();
        }
        if (Tables::has('click_log')) {
            DB::table('click_log')->where('day', '<', now()->subDays((int) Options::get('stats_keep_days'))->toDateString())->delete();
        }
    }

    /** True once the extended `visits` columns exist (migration "extend_statistics" has been run). */
    public static function extended(): bool
    {
        return Tables::has('visits') && Tables::column('visits', 'source');
    }

    /** Stable per-day visitor hash (no personal data is stored, only this digest). */
    public static function visitorHash(string $ip, string $ua): string
    {
        return substr(sha1($ip.'|'.$ua.'|'.now()->toDateString().'|'.config('app.key')), 0, 16);
    }

    /** Writes one detailed row to `visits` (never throws). */
    private static function log(\Illuminate\Http\Request $req, string $key, ?string $label, ?int $contentId): void
    {
        try {
            if (! Options::on('stats_log') || ! Tables::has('visits')) {
                return;
            }
            $ua = (string) $req->userAgent();
            $realIp = (string) $req->ip();
            $ip = Options::on('stats_log_ip') ? self::maskIp($realIp, Options::on('stats_anon_ip')) : null;
            $host = $req->getHost();
            $ref = trim((string) $req->headers->get('referer'));
            $refHost = null;
            if ($ref !== '') {
                $refHost = strtolower((string) parse_url($ref, PHP_URL_HOST)) ?: null;
                if ($refHost === $host || $refHost === 'www.'.$host || 'www.'.$refHost === $host) {
                    $ref = '';
                    $refHost = null;
                } else {
                    $ref = (string) strtok($ref, '#');
                }
            }
            $lang = strtolower(substr(trim((string) $req->getPreferredLanguage()), 0, 2)) ?: null;
            $cc = strtoupper(trim((string) ($req->server('HTTP_CF_IPCOUNTRY') ?: $req->server('HTTP_X_COUNTRY_CODE') ?: $req->server('HTTP_X_VERCEL_IP_COUNTRY') ?: '')));
            $row = [
                'created_at' => now(),
                'visitor' => self::visitorHash($realIp, $ua),
                'ip' => $ip,
                'view_key' => mb_substr($key, 0, 120),
                'content_id' => $contentId,
                'label' => $label ? mb_substr($label, 0, 190) : null,
                'path' => mb_substr($req->getRequestUri(), 0, 300),
                'referrer' => $ref !== '' ? mb_substr($ref, 0, 300) : null,
                'ref_host' => $refHost ? mb_substr($refHost, 0, 120) : null,
                'user_agent' => $ua !== '' ? mb_substr($ua, 0, 300) : null,
                'browser' => self::browser($ua),
                'os' => self::os($ua),
                'device' => self::device($ua),
                'lang' => $lang,
                'country' => preg_match('/^[A-Z]{2}$/', $cc) && ! in_array($cc, ['XX', 'T1'], true) ? $cc : null,
                'is_bot' => self::isBot($ua),
            ];
            if (self::extended()) {
                $utm = self::cleanUtm((array) $req->query());
                $src = self::classifyReferrer($refHost, $utm['utm_medium'], $utm['utm_source'], $host);
                $city = trim((string) ($req->server('HTTP_CF_IPCITY') ?: $req->server('HTTP_X_CITY') ?: ''));
                $row += $utm + [
                    'vid' => Options::on('stats_returning') && ! self::isBot($ua) ? substr(sha1($realIp.'|'.$ua.'|'.config('app.key')), 0, 16) : null,
                    'source' => $src['type'],
                    'search_engine' => $src['name'],
                    'bot' => self::botName($ua),
                    'city' => $city !== '' && preg_match('/^[\p{L}\p{M}\s\-\'.]{1,80}$/u', $city) ? $city : null,
                ];
            }
            DB::table('visits')->insert($row);
        } catch (\Throwable) {
            // the log must never break a page
        }
    }

    /** Optionally anonymises an address: IPv4 loses the last octet, IPv6 keeps the first 48 bits. */
    public static function maskIp(string $ip, bool $anon): string
    {
        if (! $anon) {
            return $ip;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        $bin = @inet_pton($ip);
        if ($bin === false) {
            return '';
        }

        return (string) inet_ntop(substr($bin, 0, 6).str_repeat("\0", 10));
    }

    public static function browser(string $ua): string
    {
        return match (true) {
            $ua === '' => '—',
            (bool) preg_match('/Edg(e|A|iOS)?\//', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/', $ua) => 'Opera',
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'YaBrowser') => 'Yandex',
            str_contains($ua, 'Vivaldi') => 'Vivaldi',
            (bool) preg_match('/Firefox|FxiOS/', $ua) => 'Firefox',
            (bool) preg_match('/Chrome|CriOS|Chromium/', $ua) => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            (bool) preg_match('/MSIE|Trident/', $ua) => 'Internet Explorer',
            default => 'Other',
        };
    }

    public static function os(string $ua): string
    {
        return match (true) {
            $ua === '' => '—',
            str_contains($ua, 'Windows') => 'Windows',
            (bool) preg_match('/iPhone|iPad|iPod/', $ua) => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'CrOS') => 'ChromeOS',
            (bool) preg_match('/Mac OS X|Macintosh/', $ua) => 'macOS',
            (bool) preg_match('/Linux|X11/', $ua) => 'Linux',
            default => 'Other',
        };
    }

    public static function device(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/iPad|Tablet|PlayBook|Silk/i', $ua) || (str_contains($ua, 'Android') && ! str_contains($ua, 'Mobile')) => 'tablet',
            (bool) preg_match('/Mobi|iPhone|iPod|Android|Phone/i', $ua) => 'mobile',
            default => 'desktop',
        };
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

    /* ================================================================ pure helpers (no database) */

    /** Crawler name for the bots report, or null for a normal browser. */
    public static function botName(string $ua): ?string
    {
        if (trim($ua) === '') {
            return 'No user agent';
        }
        $known = [
            'Googlebot' => 'Googlebot', 'Google-InspectionTool' => 'Googlebot', 'AdsBot-Google' => 'Google Ads', 'Bingbot' => 'Bingbot', 'BingPreview' => 'Bingbot',
            'YandexBot' => 'YandexBot', 'DuckDuckBot' => 'DuckDuckBot', 'Baiduspider' => 'Baiduspider', 'Applebot' => 'Applebot',
            'GPTBot' => 'GPTBot (OpenAI)', 'ChatGPT-User' => 'ChatGPT-User', 'OAI-SearchBot' => 'OAI-SearchBot', 'ClaudeBot' => 'ClaudeBot', 'Claude-Web' => 'ClaudeBot',
            'anthropic-ai' => 'ClaudeBot', 'PerplexityBot' => 'PerplexityBot', 'CCBot' => 'CCBot', 'Bytespider' => 'Bytespider', 'Amazonbot' => 'Amazonbot',
            'meta-externalagent' => 'Meta crawler', 'facebookexternalhit' => 'Facebook preview', 'Twitterbot' => 'Twitterbot', 'LinkedInBot' => 'LinkedInBot',
            'SemrushBot' => 'SemrushBot', 'AhrefsBot' => 'AhrefsBot', 'MJ12bot' => 'MJ12bot', 'DotBot' => 'DotBot', 'PetalBot' => 'PetalBot',
            'UptimeRobot' => 'UptimeRobot', 'Pingdom' => 'Pingdom', 'Lighthouse' => 'Lighthouse', 'curl/' => 'curl', 'Wget' => 'wget', 'python-requests' => 'python-requests',
        ];
        foreach ($known as $needle => $name) {
            if (stripos($ua, $needle) !== false) {
                return $name;
            }
        }

        return self::isBot($ua) ? 'Other bot' : null;
    }

    /**
     * Traffic source of a visit. Returns ['type' => direct|search|social|ai|email|paid|referral, 'name' => label|null].
     * UTM medium wins over the referrer ("email", "cpc" ...). $ownHost: the site's own host (internal referrers = direct).
     */
    public static function classifyReferrer(?string $refHost, ?string $utmMedium = null, ?string $utmSource = null, string $ownHost = ''): array
    {
        $medium = strtolower((string) $utmMedium);
        if (in_array($medium, ['email', 'e-mail', 'newsletter'], true)) {
            return ['type' => 'email', 'name' => $utmSource ? mb_substr($utmSource, 0, 40) : 'Email'];
        }
        if (in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch', 'paid_social', 'display'], true)) {
            return ['type' => 'paid', 'name' => $utmSource ? mb_substr($utmSource, 0, 40) : 'Paid'];
        }
        $h = strtolower(trim((string) $refHost));
        $h = preg_replace('/^(www|m|l|lm|mobile)\./', '', $h);
        if ($h === '' || $h === preg_replace('/^www\./', '', strtolower($ownHost))) {
            return ['type' => 'direct', 'name' => null];
        }
        // AI assistants first: gemini.google.com must not be read as Google search
        $ai = ['chatgpt.com' => 'ChatGPT', 'chat.openai.com' => 'ChatGPT', 'perplexity.ai' => 'Perplexity', 'claude.ai' => 'Claude', 'gemini.google.com' => 'Gemini',
            'copilot.microsoft.com' => 'Copilot', 'you.com' => 'You.com', 'poe.com' => 'Poe'];
        foreach ($ai as $host => $name) {
            if ($h === $host || str_ends_with($h, '.'.$host)) {
                return ['type' => 'ai', 'name' => $name];
            }
        }
        $search = ['google.' => 'Google', 'bing.com' => 'Bing', 'duckduckgo.com' => 'DuckDuckGo', 'yahoo.' => 'Yahoo', 'yandex.' => 'Yandex', 'baidu.com' => 'Baidu',
            'ecosia.org' => 'Ecosia', 'startpage.com' => 'Startpage', 'search.brave.com' => 'Brave Search', 'qwant.com' => 'Qwant', 'seznam.cz' => 'Seznam', 'abv.bg' => 'ABV', 'dir.bg' => 'Dir.bg'];
        foreach ($search as $needle => $name) {
            if (str_contains($needle, '.') && substr($needle, -1) === '.' ? str_starts_with($h, $needle) || str_contains($h, '.'.$needle) : ($h === $needle || str_ends_with($h, '.'.$needle))) {
                return ['type' => 'search', 'name' => $name];
            }
        }
        $social = ['facebook.com' => 'Facebook', 'fb.com' => 'Facebook', 'instagram.com' => 'Instagram', 'twitter.com' => 'X', 't.co' => 'X', 'x.com' => 'X',
            'linkedin.com' => 'LinkedIn', 'lnkd.in' => 'LinkedIn', 'youtube.com' => 'YouTube', 'youtu.be' => 'YouTube', 'pinterest.com' => 'Pinterest', 'reddit.com' => 'Reddit',
            'tiktok.com' => 'TikTok', 'telegram.org' => 'Telegram', 't.me' => 'Telegram', 'whatsapp.com' => 'WhatsApp', 'viber.com' => 'Viber', 'threads.net' => 'Threads', 'github.com' => 'GitHub'];
        foreach ($social as $host => $name) {
            if ($h === $host || str_ends_with($h, '.'.$host)) {
                return ['type' => 'social', 'name' => $name];
            }
        }

        return ['type' => 'referral', 'name' => mb_substr($h, 0, 40)];
    }

    /** utm_source / utm_medium / utm_campaign from a query array: trimmed, tag-free, 100 chars, null when empty. */
    public static function cleanUtm(array $query): array
    {
        $out = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign'] as $k) {
            $v = isset($query[$k]) && is_string($query[$k]) ? trim(strip_tags($query[$k])) : '';
            $out[$k] = $v !== '' ? mb_substr($v, 0, 100) : null;
        }

        return $out;
    }

    /**
     * Date range for the statistics page. $preset: today | 7 | 30 | 90 | 365 | custom. Custom needs valid Y-m-d dates
     * (swapped when reversed, capped at 730 days, never in the future). The previous period has the same length and
     * ends the day before the range starts. All dates are Y-m-d strings.
     */
    public static function resolveRange(?string $preset, ?string $from, ?string $to, \DateTimeImmutable $today): array
    {
        $day = fn (\DateTimeImmutable $d) => $d->format('Y-m-d');
        $valid = fn (?string $v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && ($d = \DateTimeImmutable::createFromFormat('!Y-m-d', $v)) && $d->format('Y-m-d') === $v;
        $preset = in_array($preset, ['today', '7', '30', '90', '365', 'custom'], true) ? $preset : '30';

        if ($preset === 'custom' && $valid($from) && $valid($to)) {
            $f = \DateTimeImmutable::createFromFormat('!Y-m-d', $from);
            $t = \DateTimeImmutable::createFromFormat('!Y-m-d', $to);
            if ($f > $t) {
                [$f, $t] = [$t, $f];
            }
            if ($t > $today) {
                $t = $today;
            }
            if ($f > $t) {
                $f = $t;
            }
            if ($t->diff($f)->days > 729) {
                $f = $t->modify('-729 days');
            }
        } else {
            $n = $preset === 'today' ? 1 : ($preset === 'custom' ? 30 : (int) $preset);
            $preset = $preset === 'custom' ? '30' : $preset;
            $t = $today;
            $f = $today->modify('-'.($n - 1).' days');
        }
        $days = (int) $f->diff($t)->days + 1;
        $pt = $f->modify('-1 day');
        $pf = $pt->modify('-'.($days - 1).' days');

        return ['preset' => $preset, 'from' => $day($f), 'to' => $day($t), 'days' => $days, 'prev_from' => $day($pf), 'prev_to' => $day($pt)];
    }

    /** Change between two numbers: ['dir' => up|down|flat|new, 'pct' => float|null]. */
    public static function delta(float $current, float $previous): array
    {
        if ($previous <= 0) {
            return ['dir' => $current > 0 ? 'new' : 'flat', 'pct' => null];
        }
        $pct = ($current - $previous) / $previous * 100;

        return ['dir' => abs($pct) < 0.5 ? 'flat' : ($pct > 0 ? 'up' : 'down'), 'pct' => round($pct, 1)];
    }

    /**
     * Fills missing days with 0 and, for long ranges (> 120 days), sums by week so a chart stays readable.
     * $perDay: ['Y-m-d' => n]. Returns ['label' => n] in date order (label = first day of the bucket).
     */
    public static function buckets(array $perDay, string $from, string $to): array
    {
        $f = new \DateTimeImmutable($from);
        $t = new \DateTimeImmutable($to);
        $weekly = (int) $f->diff($t)->days + 1 > 120;
        $out = [];
        for ($d = $f; $d <= $t; $d = $d->modify('+1 day')) {
            $label = $weekly ? $d->modify('monday this week')->format('Y-m-d') : $d->format('Y-m-d');
            if ($weekly && $label < $from) {
                $label = $from;
            }
            $out[$label] = ($out[$label] ?? 0) + (int) ($perDay[$d->format('Y-m-d')] ?? 0);
        }

        return $out;
    }

    /** Counts of timestamps per hour (0-23) and per weekday (1 = Monday ... 7 = Sunday). */
    public static function hourWeekday(array $timestamps): array
    {
        $hours = array_fill(0, 24, 0);
        $days = array_fill(1, 7, 0);
        foreach ($timestamps as $ts) {
            $t = is_numeric($ts) ? (int) $ts : strtotime((string) $ts);
            if ($t) {
                $hours[(int) date('G', $t)]++;
                $days[(int) date('N', $t)]++;
            }
        }

        return ['hours' => $hours, 'days' => $days];
    }

    /**
     * Visit paths. $rows: [['visitor' => x, 'path' => '/a'], ...] ordered by time. A "visit" = all rows of one visitor hash
     * (the hash includes the date). Returns entry/exit counts, average pages per visit and the bounce rate (one-page visits).
     */
    public static function pathStats(array $rows): array
    {
        $visits = [];
        foreach ($rows as $r) {
            $visits[$r['visitor']][] = $r['path'];
        }
        $entry = $exit = [];
        $pages = 0;
        $bounce = 0;
        foreach ($visits as $paths) {
            $entry[$paths[0]] = ($entry[$paths[0]] ?? 0) + 1;
            $last = $paths[count($paths) - 1];
            $exit[$last] = ($exit[$last] ?? 0) + 1;
            $pages += count($paths);
            $bounce += count($paths) === 1 ? 1 : 0;
        }
        arsort($entry);
        arsort($exit);
        $n = count($visits);

        return ['visits' => $n, 'entry' => $entry, 'exit' => $exit, 'avg_pages' => $n ? round($pages / $n, 2) : 0.0, 'bounce' => $n ? round($bounce / $n * 100, 1) : 0.0];
    }

    /** New vs returning. $firstSeen: [vid => first time ever seen]. A visitor first seen before $from is returning. */
    public static function splitReturning(array $firstSeen, string $from): array
    {
        $new = $ret = 0;
        foreach ($firstSeen as $first) {
            substr((string) $first, 0, 10) < $from ? $ret++ : $new++;
        }

        return ['new' => $new, 'returning' => $ret];
    }

    /** Label for tracked clicks: tags removed, whitespace collapsed, 120 chars. */
    public static function cleanLabel(string $label): string
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($label))), 0, 120);
    }

    /** CSV cell safe against spreadsheet formula injection. */
    public static function csvCell(mixed $v): string
    {
        $v = (string) $v;

        return preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;
    }

    /* ================================================================ writers (public endpoints) */

    /** Time on page and scroll depth sent by the page when it is left. Updates the latest matching visit of today. */
    public static function ping(\Illuminate\Http\Request $req): void
    {
        try {
            if (! Options::on('stats_on') || ! Options::on('stats_log') || ! Options::on('stats_engage') || ! self::extended()) {
                return;
            }
            $ua = (string) $req->userAgent();
            if (self::isBot($ua) || (Options::on('stats_skip_logged') && auth()->check())) {
                return;
            }
            $path = (string) $req->input('p', '');
            $sec = $req->input('t');
            $scroll = $req->input('s');
            if ($path === '' || $path[0] !== '/' || strlen($path) > 300 || ! is_numeric($sec) || ! is_numeric($scroll)) {
                return;
            }
            $sec = max(0, min(3600, (int) $sec));
            $scroll = max(0, min(100, (int) $scroll));
            $visitor = self::visitorHash((string) $req->ip(), $ua);
            $id = DB::table('visits')->where('visitor', $visitor)->where('path', $path)->where('is_bot', false)
                ->where('created_at', '>=', now()->subHours(6))->orderByDesc('id')->value('id');
            if ($id) {
                DB::table('visits')->where('id', $id)->update([
                    'seconds' => DB::raw('GREATEST(COALESCE(seconds, 0), '.$sec.')'),
                    'scroll' => DB::raw('GREATEST(COALESCE(scroll, 0), '.$scroll.')'),
                ]);
            }
        } catch (\Throwable) {
            // best effort
        }
    }

    /** Counts a tracked click (data-track attribute, mailto:, tel:, outbound link, download). */
    public static function click(\Illuminate\Http\Request $req): void
    {
        try {
            if (! Options::on('stats_on') || ! Options::on('stats_clicks') || ! Tables::has('click_log')) {
                return;
            }
            if (self::isBot((string) $req->userAgent()) || (Options::on('stats_skip_logged') && auth()->check())) {
                return;
            }
            $kind = (string) $req->input('k', 'custom');
            $label = self::cleanLabel((string) $req->input('l', ''));
            if ($label === '' || ! in_array($kind, ['custom', 'mailto', 'tel', 'external', 'download'], true)) {
                return;
            }
            DB::table('click_log')->upsert([['day' => now()->toDateString(), 'label' => $label, 'kind' => $kind, 'clicks' => 1]], ['day', 'label', 'kind'], ['clicks' => DB::raw('clicks + 1')]);
        } catch (\Throwable) {
            // best effort
        }
    }

    /** Records an on-site search ($results = number of hits; 0 counts as "found nothing"). */
    public static function searchTerm(string $term, int $results): void
    {
        try {
            if (! Options::on('stats_on') || ! Options::on('stats_search_terms') || ! Tables::has('search_terms')) {
                return;
            }
            $req = request();
            if (self::isBot((string) $req->userAgent()) || (Options::on('stats_skip_logged') && auth()->check())) {
                return;
            }
            $term = mb_strtolower(self::cleanLabel($term));
            if (mb_strlen($term) < 2) {
                return;
            }
            DB::table('search_terms')->upsert(
                [['day' => now()->toDateString(), 'term' => mb_substr($term, 0, 100), 'searches' => 1, 'zero' => $results === 0 ? 1 : 0]],
                ['day', 'term'],
                ['searches' => DB::raw('searches + 1'), 'zero' => DB::raw('zero + '.($results === 0 ? 1 : 0))]
            );
        } catch (\Throwable) {
            // best effort
        }
    }

    /* ================================================================ reports for the admin page */

    /** Day totals of the aggregate counter inside a range: ['Y-m-d' => hits]. */
    public static function dailyHits(string $from, string $to): array
    {
        if (! Tables::has('page_views')) {
            return [];
        }

        return DB::table('page_views')->whereBetween('day', [$from, $to])->selectRaw('day, SUM(hits) as total')->groupBy('day')->pluck('total', 'day')->map(fn ($n) => (int) $n)->all();
    }

    /** Headline numbers for a range, with the previous period for comparison. */
    public static function summary(array $r): array
    {
        if (! Tables::has('page_views')) {
            return [];
        }
        $sum = fn (string $f, string $t) => (int) DB::table('page_views')->whereBetween('day', [$f, $t])->sum('hits');
        $cur = $sum($r['from'], $r['to']);
        $prev = $sum($r['prev_from'], $r['prev_to']);
        $uniq = ['cur' => null, 'prev' => null];
        if (Tables::has('visits')) {
            $u = fn (string $f, string $t) => (int) DB::table('visits')->where('is_bot', false)->where('created_at', '>=', $f.' 00:00:00')->where('created_at', '<=', $t.' 23:59:59')->distinct()->count('visitor');
            // "unique visitors" = distinct daily hashes, i.e. visitor-days
            $uniq = ['cur' => $u($r['from'], $r['to']), 'prev' => $u($r['prev_from'], $r['prev_to'])];
        }

        return [
            'hits' => $cur, 'hits_prev' => $prev, 'hits_delta' => self::delta($cur, $prev),
            'uniq' => $uniq['cur'], 'uniq_prev' => $uniq['prev'], 'uniq_delta' => $uniq['cur'] === null ? null : self::delta($uniq['cur'], (int) $uniq['prev']),
            'today' => $sum(now()->toDateString(), now()->toDateString()),
            'top' => DB::table('page_views')->whereBetween('day', [$r['from'], $r['to']])
                ->selectRaw('view_key, MAX(label) as label, SUM(hits) as total')->groupBy('view_key')->orderByDesc('total')->limit(10)->get(),
            'daily' => self::dailyHits($r['from'], $r['to']),
            'avg_per_day' => round($cur / max(1, $r['days']), 1),
        ];
    }

    /** Breakdowns from the detailed log for a range (+ newest visits, optionally for one IP). */
    public static function details(array $r, ?string $ip = null, int $limit = 50): array
    {
        if (! Tables::has('visits')) {
            return [];
        }
        $from = $r['from'].' 00:00:00';
        $to = $r['to'].' 23:59:59';
        $base = fn () => DB::table('visits')->where('created_at', '>=', $from)->where('created_at', '<=', $to)->where('is_bot', false);
        $top = fn (string $col, int $n = 8) => $base()->whereNotNull($col)->where($col, '!=', '')
            ->selectRaw("$col as name, COUNT(*) as total")->groupBy($col)->orderByDesc('total')->limit($n)->get();
        $recent = DB::table('visits')->orderByDesc('id')->limit($limit);
        if ($ip !== null && $ip !== '') {
            $recent->where('ip', $ip);
        }
        $d = [
            'uniq_today' => (int) DB::table('visits')->where('created_at', '>=', now()->startOfDay())->where('is_bot', false)->distinct()->count('visitor'),
            'uniq_range' => (int) $base()->distinct()->count('visitor'),
            'ips_range' => (int) $base()->whereNotNull('ip')->distinct()->count('ip'),
            'referrers' => $top('ref_host'),
            'browsers' => $top('browser'),
            'systems' => $top('os'),
            'devices' => $top('device'),
            'countries' => $top('country'),
            'langs' => $top('lang'),
            'ips' => $base()->whereNotNull('ip')->selectRaw('ip, COUNT(*) as total, MAX(created_at) as last_seen, MAX(country) as country')->groupBy('ip')->orderByDesc('total')->limit(10)->get(),
            'recent' => $recent->get(),
            'filter' => $ip,
            'extended' => self::extended(),
        ];
        if (! $d['extended']) {
            return $d;
        }

        // traffic sources: by type, by name, UTM campaigns
        $d['source_types'] = $base()->whereNotNull('source')->selectRaw('source as name, COUNT(*) as total')->groupBy('source')->orderByDesc('total')->get();
        $d['source_names'] = $base()->whereNotNull('search_engine')->selectRaw('source, search_engine as name, COUNT(*) as total')->groupBy('source', 'search_engine')->orderByDesc('total')->limit(15)->get();
        $d['campaigns'] = $base()->whereNotNull('utm_source')->selectRaw('utm_source, utm_medium, utm_campaign, COUNT(*) as total')->groupBy('utm_source', 'utm_medium', 'utm_campaign')->orderByDesc('total')->limit(15)->get();
        $d['cities'] = $top('city', 10);

        // peak hours and weekdays (from raw timestamps, capped for shared hosting)
        $d['peaks'] = self::hourWeekday($base()->orderByDesc('id')->limit(30000)->pluck('created_at')->all());

        // entry / exit pages, pages per visit, bounce rate
        $rows = $base()->orderBy('id')->limit(30000)->get(['visitor', 'path', 'label'])->map(fn ($x) => ['visitor' => $x->visitor, 'path' => strtok($x->path, '?') ?: $x->path])->all();
        $d['paths'] = self::pathStats($rows);

        // bots
        $botBase = fn () => DB::table('visits')->where('created_at', '>=', $from)->where('created_at', '<=', $to)->where('is_bot', true);
        $d['bot_total'] = (int) $botBase()->count();
        $d['human_total'] = (int) $base()->count();
        $d['bots'] = $botBase()->selectRaw("COALESCE(bot, 'Other bot') as name, COUNT(*) as total, MAX(created_at) as last_seen")->groupBy('bot')->orderByDesc('total')->limit(15)->get();

        // time on page and scroll depth
        $d['engage'] = [
            'avg_seconds' => (int) round((float) $base()->whereNotNull('seconds')->avg('seconds')),
            'avg_scroll' => (int) round((float) $base()->whereNotNull('scroll')->avg('scroll')),
            'measured' => (int) $base()->whereNotNull('seconds')->count(),
            'pages' => $base()->whereNotNull('seconds')->whereNotNull('label')
                ->selectRaw('label, COUNT(*) as views, AVG(seconds) as avg_seconds, AVG(scroll) as avg_scroll')->groupBy('label')->havingRaw('COUNT(*) >= 3')->orderByDesc('views')->limit(10)->get(),
        ];

        // new vs returning (needs "returning visitors" option; vid is filled only then)
        $d['returning'] = null;
        if (Options::on('stats_returning')) {
            $vids = $base()->whereNotNull('vid')->distinct()->limit(30000)->pluck('vid')->all();
            if ($vids) {
                $first = [];
                foreach (array_chunk($vids, 500) as $chunk) {
                    foreach (DB::table('visits')->whereIn('vid', $chunk)->selectRaw('vid, MIN(created_at) as first_at')->groupBy('vid')->get() as $x) {
                        $first[$x->vid] = $x->first_at;
                    }
                }
                $d['returning'] = self::splitReturning($first, $r['from']);
            }
        }

        return $d;
    }

    /** Search terms, tracked clicks and content-group tables for a range. */
    public static function extras(array $r): array
    {
        $x = ['terms' => collect(), 'zero_terms' => collect(), 'clicks' => collect(), 'by_category' => collect(), 'by_author' => collect(), 'posts_daily' => [], 'comments_daily' => []];
        if (Tables::has('search_terms')) {
            $q = fn () => DB::table('search_terms')->whereBetween('day', [$r['from'], $r['to']])->selectRaw('term, SUM(searches) as searches, SUM(zero) as zero')->groupBy('term');
            $x['terms'] = $q()->orderByDesc('searches')->limit(15)->get();
            $x['zero_terms'] = $q()->havingRaw('SUM(zero) > 0')->orderByDesc('zero')->limit(15)->get();
        }
        if (Tables::has('click_log')) {
            $x['clicks'] = DB::table('click_log')->whereBetween('day', [$r['from'], $r['to']])->selectRaw('label, kind, SUM(clicks) as total')->groupBy('label', 'kind')->orderByDesc('total')->limit(20)->get();
        }
        if (Tables::has('page_views')) {
            $pv = fn () => DB::table('page_views as v')->join('contents as c', 'c.id', '=', 'v.content_id')->whereBetween('v.day', [$r['from'], $r['to']]);
            $x['by_category'] = $pv()->whereNotNull('c.category_id')->selectRaw('c.category_id as id, SUM(v.hits) as total')->groupBy('c.category_id')->orderByDesc('total')->limit(10)->get()
                ->map(function ($row) {
                    $row->name = optional(\App\Models\Category::with('translations')->find($row->id))->adminName() ?? '#'.$row->id;

                    return $row;
                });
            $x['by_author'] = $pv()->where('c.type', 'post')->whereNotNull('c.author_id')->selectRaw('c.author_id as id, SUM(v.hits) as total')->groupBy('c.author_id')->orderByDesc('total')->limit(10)->get()
                ->map(function ($row) {
                    $row->name = optional(\App\Models\User::find($row->id))->name ?? '#'.$row->id;

                    return $row;
                });
        }
        // publishing activity next to the traffic chart
        $x['posts_daily'] = DB::table('contents')->where('type', 'post')->where('status', 'published')->whereNotNull('publish_at')
            ->where('publish_at', '>=', $r['from'].' 00:00:00')->where('publish_at', '<=', $r['to'].' 23:59:59')
            ->selectRaw('DATE(publish_at) as d, COUNT(*) as n')->groupBy('d')->pluck('n', 'd')->map(fn ($n) => (int) $n)->all();
        if (Discussion::ready()) {
            $x['comments_daily'] = DB::table('comments')->where('created_at', '>=', $r['from'].' 00:00:00')->where('created_at', '<=', $r['to'].' 23:59:59')
                ->selectRaw('DATE(created_at) as d, COUNT(*) as n')->groupBy('d')->pluck('n', 'd')->map(fn ($n) => (int) $n)->all();
        }

        return $x;
    }
}
