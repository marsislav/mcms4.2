<?php

namespace App\Support;

/**
 * WordPress-like site options (Settings tabs: Четене, Писане, Дата и час, Връзки, Медия,
 * Скриптове, Поддръжка). Stored in the `settings` table as `opt.<key>` — no migration needed.
 * clean() is pure PHP (no Laravel) so it can be unit-tested; get() reads through setting().
 */
class Options
{
    public const GROUPS = [
        'reading' => 'Четене',
        'writing' => 'Писане',
        'datetime' => 'Дата и час',
        'permalinks' => 'Връзки',
        'media' => 'Медия',
        'scripts' => 'Скриптове',
        'users' => 'Потребители',
        'maintenance' => 'Поддръжка',
    ];

    public const DEFAULTS = [
        // reading
        'front_mode' => 'page', 'feed_count' => '20', 'feed_full' => '0', 'noindex' => '0', 'excerpt_length' => '160', 'show_author' => '1',
        'reading_time' => '1', 'show_updated' => '0', 'share_buttons' => '0', 'back_to_top' => '0',
        // users
        'registration' => '0', 'default_role' => 'subscriber', 'reg_approval' => '0', 'reg_notify' => '1',
        // writing
        'default_category' => '', 'default_status' => 'draft',
        // date & time
        'timezone' => '', 'date_format' => 'j F Y', 'time_format' => 'H:i',
        // permalinks
        'blog_base' => 'blog', 'category_base' => 'category', 'tag_base' => 'tag',
        // media
        'upload_max_mb' => '', 'image_max_px' => '', 'thumb_px' => '',
        // scripts
        'scripts_head' => '', 'scripts_footer' => '', 'csp_hosts' => '', 'custom_css' => '',
        // maintenance
        'maintenance_message' => '', 'maintenance_ips' => '',
    ];

    public const DATE_FORMATS = ['j F Y' => 1, 'd.m.Y' => 1, 'Y-m-d' => 1, 'F j, Y' => 1, 'j M Y' => 1, 'd/m/Y' => 1];
    public const TIME_FORMATS = ['H:i' => '17:30', 'g:i a' => '5:30 pm', 'g:i A' => '5:30 PM'];

    public const BASE_KEYS = ['blog_base', 'category_base', 'tag_base'];
    /** Root URLs that are real routes and can't be used as the blog/category/tag prefix. */
    public const RESERVED_BASES = [
        'search', 'contact', 'comment', 'install', 'sitemap', 'robots', 'feed', 'uploads', 'assets', 'storage', 'vendor',
        'up', 'admin', 'panel', 'login', 'logout', 'api', 'design', 'app', 'config', 'database', 'resources', 'routes',
        'plugins', 'lang', 'bootstrap', 'tests', 'public',
    ];

    /* ------------------------------------------------------------ reading */

    public static function get(string $key): string
    {
        $v = null;
        try {
            $v = function_exists('setting') ? setting('opt.'.$key) : ($GLOBALS['S']['opt.'.$key] ?? null);
        } catch (\Throwable) {
            $v = null; // database not reachable yet (install, outage): fall back to defaults
        }

        return ($v === null || $v === '') ? (self::DEFAULTS[$key] ?? '') : (string) $v;
    }

    public static function on(string $key): bool
    {
        return self::get($key) === '1';
    }

    public static function all(): array
    {
        $o = [];
        foreach (self::DEFAULTS as $k => $_) {
            $o[$k] = self::get($k);
        }

        return $o;
    }

    public static function excerptLength(): int
    {
        return max(50, min(600, (int) self::get('excerpt_length')));
    }

    public static function feedCount(): int
    {
        return max(1, min(50, (int) self::get('feed_count')));
    }

    public static function timezone(): ?string
    {
        $tz = self::get('timezone');

        return ($tz !== '' && in_array($tz, \DateTimeZone::listIdentifiers(), true)) ? $tz : null;
    }

    /** Numeric override for config() or null = keep the config/.env value. */
    public static function mediaInt(string $key): ?int
    {
        $v = self::get($key);

        return ctype_digit($v) && (int) $v > 0 ? (int) $v : null;
    }

    public static function lines(string $key): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', self::get($key)) ?: []), fn ($l) => $l !== ''));
    }

    /** Extra hosts allowed in the Content-Security-Policy (script-src / connect-src). */
    public static function cspHosts(): array
    {
        return array_values(array_filter(self::lines('csp_hosts'), fn ($h) => self::validHost($h)));
    }

    public static function validHost(string $h): bool
    {
        return (bool) preg_match('~^https://(\*\.)?[a-z0-9]([a-z0-9.\-]*[a-z0-9])?(:\d{1,5})?$~i', $h);
    }

    /** Admin-supplied HTML for <head> / end of <body>, with the CSP nonce added to every <script>. */
    public static function scripts(string $where, string $nonce): string
    {
        $html = self::get($where === 'head' ? 'scripts_head' : 'scripts_footer');
        if (trim($html) === '') {
            return '';
        }
        $html = preg_replace('/(<script\b[^>]*?)\snonce\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]*)/i', '$1', $html);

        return preg_replace('/<script\b/i', '<script nonce="'.$nonce.'"', $html);
    }

    /** Admin CSS: no markup, no @import / expression() / javascript: (it is served as /design.css). */
    public static function cleanCss(string $css): string
    {
        $css = str_replace(['<', '>', "\0"], '', $css);
        $css = preg_replace('/@import\b[^;]*;?/i', '', $css);
        $css = preg_replace('/expression\s*\(|javascript\s*:|behavior\s*:|-moz-binding/i', '', $css);

        return trim($css);
    }

    /** Estimated reading time in minutes (200 words/min, at least 1). */
    public static function readingMinutes(?string $html): int
    {
        $words = preg_match_all('/\p{L}[\p{L}\p{N}\'’-]*/u', strip_tags((string) $html));

        return max(1, (int) ceil(($words ?: 0) / 200));
    }

    /** Maintenance-mode visitors allowed through (empty list = nobody by IP). */
    public static function ipAllowed(string $ip): bool
    {
        return in_array($ip, self::lines('maintenance_ips'), true);
    }

    /* ---------------------------------------------------------- permalinks */

    /**
     * Old URL (default /blog/category/x or the previous prefix) -> new URL after the prefixes were renamed.
     * $path has no leading slash. Returns the new path or null.
     */
    public static function legacyPath(string $path, string $blog, string $cat, string $tag): ?string
    {
        $segs = explode('/', trim($path, '/'));
        $i = 0;
        $prefix = [];
        if (count($segs) > 1 && preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $segs[0])) {
            $prefix[] = array_shift($segs);
        }
        if (! $segs || ($segs[0] !== 'blog' && $segs[0] !== $blog)) {
            return null;
        }
        $segs[0] = $blog;
        if (count($segs) === 3) {
            if ($segs[1] === 'category' || $segs[1] === $cat) {
                $segs[1] = $cat;
            } elseif ($segs[1] === 'tag' || $segs[1] === $tag) {
                $segs[1] = $tag;
            }
        }
        $new = implode('/', array_merge($prefix, $segs));

        return $new === trim($path, '/') ? null : $new;
    }

    /* ------------------------------------------------------------- writing */

    /**
     * Validate one group. $ctx: 'reserved' => slugs, 'taken' => slugs used by content, 'categories' => valid ids,
     * 'current' => current bases (for the duplicate check) — all optional.
     *
     * @return array{values: array<string,string>, errors: string[]}
     */
    public static function clean(string $group, array $in, array $ctx = []): array
    {
        $v = [];
        $err = [];
        $int = fn ($k, $min, $max, $def) => (string) (isset($in[$k]) && is_numeric($in[$k]) ? max($min, min($max, (int) $in[$k])) : $def);
        $bool = fn ($k) => ! empty($in[$k]) && $in[$k] !== '0' ? '1' : '0';
        $enum = fn ($k, array $allowed, $def) => in_array((string) ($in[$k] ?? ''), $allowed, true) ? (string) $in[$k] : $def;

        switch ($group) {
            case 'reading':
                $v['front_mode'] = $enum('front_mode', ['page', 'posts'], 'page');
                $v['feed_count'] = $int('feed_count', 1, 50, 20);
                $v['feed_full'] = $bool('feed_full');
                $v['noindex'] = $bool('noindex');
                $v['excerpt_length'] = $int('excerpt_length', 50, 600, 160);
                $v['show_author'] = $bool('show_author');
                foreach (['reading_time', 'show_updated', 'share_buttons', 'back_to_top'] as $k) {
                    $v[$k] = $bool($k);
                }
                break;

            case 'users':
                $v['registration'] = $bool('registration');
                $v['default_role'] = $enum('default_role', ['subscriber', 'contributor', 'author'], 'subscriber');
                $v['reg_approval'] = $bool('reg_approval');
                $v['reg_notify'] = $bool('reg_notify');
                break;

            case 'writing':
                $cat = (string) ($in['default_category'] ?? '');
                $valid = $ctx['categories'] ?? null;
                $v['default_category'] = ctype_digit($cat) && ($valid === null || in_array((int) $cat, $valid, true)) ? $cat : '';
                $v['default_status'] = $enum('default_status', ['draft', 'published'], 'draft');
                break;

            case 'datetime':
                $tz = (string) ($in['timezone'] ?? '');
                if ($tz !== '' && ! in_array($tz, \DateTimeZone::listIdentifiers(), true)) {
                    $err[] = __('Непозната часова зона.');
                    $tz = '';
                }
                $v['timezone'] = $tz;
                $v['date_format'] = $enum('date_format', array_keys(self::DATE_FORMATS), 'j F Y');
                $v['time_format'] = $enum('time_format', array_keys(self::TIME_FORMATS), 'H:i');
                break;

            case 'permalinks':
                $labels = ['blog_base' => __('Блог'), 'category_base' => __('Категории'), 'tag_base' => __('Тагове')];
                $reserved = array_merge(self::RESERVED_BASES, $ctx['reserved'] ?? []);
                $taken = $ctx['taken'] ?? [];
                foreach (self::BASE_KEYS as $k) {
                    $val = strtolower(trim((string) ($in[$k] ?? '')));
                    $val = trim($val, '/');
                    if ($val === '') {
                        $val = self::DEFAULTS[$k];
                    }
                    if (! preg_match('/^[a-z0-9][a-z0-9\-]{0,39}$/', $val)) {
                        $err[] = __(':label: само малки латински букви, цифри и тире (до 40 знака).', ['label' => $labels[$k]]);
                        $val = $ctx['current'][$k] ?? self::DEFAULTS[$k];
                    } elseif ($k === 'blog_base' && (in_array($val, $reserved, true) || preg_match('/^[a-z]{2}$/', $val))) {
                        $err[] = __('„:val“ е заето от системата — изберете друг адрес за блога.', ['val' => $val]);
                        $val = $ctx['current'][$k] ?? self::DEFAULTS[$k];
                    } elseif ($k === 'blog_base' && in_array($val, $taken, true)) {
                        $err[] = __('Съществува страница с адрес „:val“ — преименувайте я или изберете друг адрес.', ['val' => $val]);
                        $val = $ctx['current'][$k] ?? self::DEFAULTS[$k];
                    }
                    $v[$k] = $val;
                }
                if ($v['category_base'] === $v['tag_base']) {
                    $err[] = __('Категориите и таговете не могат да имат един и същи адрес.');
                    $v['category_base'] = self::DEFAULTS['category_base'];
                    $v['tag_base'] = self::DEFAULTS['tag_base'];
                }
                break;

            case 'media':
                // empty = keep the value from config / .env
                $v['upload_max_mb'] = isset($in['upload_max_mb']) && $in['upload_max_mb'] !== '' ? $int('upload_max_mb', 1, 64, 8) : '';
                $v['image_max_px'] = isset($in['image_max_px']) && $in['image_max_px'] !== '' ? $int('image_max_px', 800, 6000, 2560) : '';
                $v['thumb_px'] = isset($in['thumb_px']) && $in['thumb_px'] !== '' ? $int('thumb_px', 100, 1200, 480) : '';
                break;

            case 'scripts':
                $css = self::cleanCss((string) ($in['custom_css'] ?? ''));
                if (mb_strlen($css) > 20000) {
                    $err[] = __('Кодът е твърде дълъг (макс. 20 000 знака).');
                    $css = mb_substr($css, 0, 20000);
                }
                $v['custom_css'] = $css;
                foreach (['scripts_head', 'scripts_footer'] as $k) {
                    $s = trim((string) ($in[$k] ?? ''));
                    if (mb_strlen($s) > 20000) {
                        $err[] = __('Кодът е твърде дълъг (макс. 20 000 знака).');
                        $s = mb_substr($s, 0, 20000);
                    }
                    $v[$k] = $s;
                }
                $hosts = [];
                foreach (preg_split('/\R/', (string) ($in['csp_hosts'] ?? '')) ?: [] as $h) {
                    $h = rtrim(trim($h), '/');
                    if ($h === '') {
                        continue;
                    }
                    if (! self::validHost($h)) {
                        $err[] = __('Невалиден адрес „:val“ — пример: https://www.googletagmanager.com', ['val' => $h]);
                        continue;
                    }
                    $hosts[strtolower($h)] = strtolower($h);
                }
                if (count($hosts) > 15) {
                    $err[] = __('Най-много 15 разрешени адреса.');
                    $hosts = array_slice($hosts, 0, 15, true);
                }
                $v['csp_hosts'] = implode("\n", $hosts);
                break;

            case 'maintenance':
                $v['maintenance_message'] = mb_substr(trim(strip_tags((string) ($in['maintenance_message'] ?? ''))), 0, 500);
                $ips = [];
                foreach (preg_split('/\R/', (string) ($in['maintenance_ips'] ?? '')) ?: [] as $ip) {
                    $ip = trim($ip);
                    if ($ip === '') {
                        continue;
                    }
                    if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                        $err[] = __('Невалиден IP адрес „:val“.', ['val' => $ip]);
                        continue;
                    }
                    $ips[$ip] = $ip;
                }
                $v['maintenance_ips'] = implode("\n", array_slice($ips, 0, 20));
                break;
        }

        return ['values' => $v, 'errors' => $err];
    }
}
