<?php

namespace App\Support;

/**
 * Widget areas (WordPress-style): footer columns (1-12) and the blog sidebar.
 * Stored as JSON in `settings`: widgets.footer.c1 … c12, widgets.sidebar (+ footer.cols, sidebar.on, sidebar.side).
 * clean() is pure PHP (testable); everything the site prints is escaped or already sanitised here.
 */
class Widgets
{
    public const TYPES = [
        'html' => 'Текст / HTML',
        'menu' => 'Меню',
        'recent_posts' => 'Последни публикации',
        'categories' => 'Категории',
        'tags' => 'Тагове',
        'recent_comments' => 'Последни коментари',
        'search' => 'Търсене',
        'social' => 'Социални мрежи',
        'account' => 'Вход / Регистрация',
    ];

    public const MAX_COLS = 12;
    public const MAX_WIDGETS = 20; // per column / sidebar
    public const MAX_JSON = 60000; // settings.value is TEXT (64 KB)

    /* ------------------------------------------------------------ reading */

    /** @return array<int,array> */
    public static function decode(?string $json): array
    {
        $a = $json ? json_decode($json, true) : null;

        return is_array($a) ? array_values(array_filter($a, fn ($w) => is_array($w) && isset(self::TYPES[$w['type'] ?? '']))) : [];
    }

    /** 0 = classic footer (site name + footer menu + social). */
    public static function footerCols(): int
    {
        return max(0, min(self::MAX_COLS, (int) setting('footer.cols', 0)));
    }

    /** @return array{cols:int,has:bool,columns:array<int,array>} */
    public static function footer(): array
    {
        $cols = self::footerCols();
        $columns = [];
        $has = false;
        for ($i = 1; $i <= $cols; $i++) {
            $columns[$i] = self::decode(setting('widgets.footer.c'.$i));
            $has = $has || $columns[$i] !== [];
        }

        return ['cols' => $cols, 'has' => $has, 'columns' => $columns];
    }

    /** @return array{on:bool,side:string,widgets:array} */
    public static function sidebar(): array
    {
        $side = setting('sidebar.side', 'right');

        return [
            'on' => setting('sidebar.on', '0') === '1',
            'side' => $side === 'left' ? 'left' : 'right',
            'widgets' => self::decode(setting('widgets.sidebar')),
        ];
    }

    /** Per-language field with fallback to the default language, then any. */
    public static function text(array $w, string $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $v = $w[$field] ?? [];
        if (! is_array($v)) {
            return (string) $v;
        }
        foreach ([$locale, mcms_default_locale()] as $l) {
            if (isset($v[$l]) && trim((string) $v[$l]) !== '') {
                return (string) $v[$l];
            }
        }
        foreach ($v as $x) {
            if (trim((string) $x) !== '') {
                return (string) $x;
            }
        }

        return '';
    }

    /* ------------------------------------------------------------ writing */

    /**
     * Validate/normalise one submitted widget. Returns null for unknown types.
     * @param  string[]  $locales  active language codes
     */
    public static function clean(array $raw, array $locales): ?array
    {
        $type = (string) ($raw['type'] ?? '');
        if (! isset(self::TYPES[$type])) {
            return null;
        }

        $w = [
            'id' => preg_match('/^[a-f0-9]{8}$/', (string) ($raw['id'] ?? '')) ? $raw['id'] : bin2hex(random_bytes(4)),
            'type' => $type,
            'title' => self::perLocale($raw['title'] ?? [], $locales, 120, false),
            'icon' => self::cleanIcon($raw['icon'] ?? null),
            'class' => self::cleanClass($raw['class'] ?? null),
        ];

        switch ($type) {
            case 'html':
                $w['body'] = self::perLocale($raw['body'] ?? [], $locales, 20000, true);
                break;
            case 'menu':
                $m = (string) ($raw['menu'] ?? '');
                $w['menu'] = preg_match('/^[a-z0-9_\-]{1,40}$/', $m) ? $m : 'footer';
                break;
            case 'recent_posts':
                $w['count'] = self::int($raw['count'] ?? 5, 1, 20, 5);
                $w['show_date'] = self::bool($raw['show_date'] ?? null);
                $w['show_thumb'] = self::bool($raw['show_thumb'] ?? null);
                break;
            case 'categories':
                $w['show_count'] = self::bool($raw['show_count'] ?? null);
                $w['hide_empty'] = self::bool($raw['hide_empty'] ?? null);
                break;
            case 'tags':
                $w['limit'] = self::int($raw['limit'] ?? 30, 1, 100, 30);
                $w['style'] = ($raw['style'] ?? '') === 'list' ? 'list' : 'cloud';
                $w['show_count'] = self::bool($raw['show_count'] ?? null);
                break;
            case 'recent_comments':
                $w['count'] = self::int($raw['count'] ?? 5, 1, 20, 5);
                break;
        }

        return $w;
    }

    /** @return array<int,array> cleaned list (max MAX_WIDGETS), order = submitted order */
    public static function cleanList(array $rows, array $locales): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (is_array($row) && ($w = self::clean($row, $locales))) {
                $out[] = $w;
            }
            if (count($out) >= self::MAX_WIDGETS) {
                break;
            }
        }

        return $out;
    }

    private static function perLocale(mixed $v, array $locales, int $max, bool $html): array
    {
        $out = [];
        foreach ($locales as $loc) {
            $s = is_array($v) ? (string) ($v[$loc] ?? '') : '';
            $s = trim($s);
            if ($s === '') {
                continue;
            }
            $out[$loc] = $html ? Sanitizer::clean(mb_substr($s, 0, $max)) : mb_substr(trim(strip_tags($s)), 0, $max);
        }

        return $out;
    }

    /** "fa-solid fa-house" (free styles only) or null. */
    public static function cleanIcon(?string $v): ?string
    {
        $v = trim(preg_replace('/\s+/', ' ', (string) $v));

        return preg_match('/^fa-(solid|regular|brands) fa-[a-z0-9-]{1,60}$/', $v) ? $v : null;
    }

    public static function cleanClass(?string $v): ?string
    {
        $v = trim(preg_replace('/\s+/', ' ', (string) $v));

        return ($v !== '' && strlen($v) <= 120 && preg_match('/^[A-Za-z_][A-Za-z0-9_\-]*( [A-Za-z_][A-Za-z0-9_\-]*)*$/', $v)) ? $v : null;
    }

    private static function int(mixed $v, int $min, int $max, int $default): int
    {
        return is_numeric($v) ? max($min, min($max, (int) $v)) : $default;
    }

    private static function bool(mixed $v): bool
    {
        return in_array($v, [true, 1, '1', 'on', 'true'], true);
    }
}
