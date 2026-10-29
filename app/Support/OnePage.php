<?php

namespace App\Support;

use App\Models\Content;

/**
 * One-page mode: the home page becomes a single long page made of sections (the home page first, then the
 * pages picked in Settings > Design). Menu links to those pages turn into #anchors.
 */
class OnePage
{
    public const SLOTS = 10;

    public const ANIMS = [
        'none' => 'Без анимация',
        'fade-up' => 'Излиза отдолу (fade up)',
        'fade-down' => 'Слиза отгоре (fade down)',
        'fade-left' => 'Влиза отляво',
        'fade-right' => 'Влиза отдясно',
        'zoom-in' => 'Приближава се (zoom in)',
        'zoom-out' => 'Смалява се (zoom out)',
        'blur-in' => 'Избистря се (blur)',
        'clip-up' => 'Разкрива се отдолу нагоре',
        'clip-left' => 'Разкрива се отляво надясно',
        'flip-x' => 'Преобръща се (flip)',
        'rotate-in' => 'Завърта се и влиза',
        'skew-up' => 'Накланя се и излиза',
        'bounce' => 'Подскача',
    ];

    public const TITLE = ['none' => 'Без (като останалия текст)', 'words' => 'Думите една по една', 'chars' => 'Буква по буква'];

    private static ?array $cache = null;

    public static function active(): bool
    {
        return Design::get('op_on') === '1' && Options::get('front_mode') !== 'posts' && self::homeId() !== null;
    }

    private static function homeId(): ?int
    {
        $id = Content::query()->visible()->where('type', 'page')->where('is_home', true)->value('id');

        return $id ? (int) $id : null;
    }

    /** Whitelist the posted rows: [{c: page id, a: animation|default}], unique, in posted order. */
    public static function normalize(array $rows, array $validIds): array
    {
        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['c']) || ! is_scalar($row['c']) || ! ctype_digit((string) $row['c'])) {
                continue;
            }
            $c = (int) $row['c'];
            if ($c < 1 || isset($seen[$c]) || ! in_array($c, $validIds, true)) {
                continue;
            }
            $a = (isset($row['a']) && is_string($row['a']) && isset(self::ANIMS[$row['a']])) ? $row['a'] : 'default';
            $seen[$c] = true;
            $out[] = ['c' => $c, 'a' => $a];
            if (count($out) >= self::SLOTS) {
                break;
            }
        }

        return $out;
    }

    public static function rows(): array
    {
        $raw = function_exists('setting') ? setting('design.op_sections') : null;
        $d = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        return is_array($d) ? array_values(array_filter($d, 'is_array')) : [];
    }

    /** @return array<int, array{id:string,content:Content,tr:\App\Models\ContentTranslation,anim:string,label:string}> */
    public static function sections(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $global = Design::get('op_anim');
        $rows = [];
        if ($hid = self::homeId()) {
            $rows[] = ['c' => $hid, 'a' => 'default'];
        }
        foreach (self::rows() as $r) {
            if ((int) ($r['c'] ?? 0) !== $hid) {
                $rows[] = $r;
            }
        }
        $contents = Content::query()->visible()->where('type', 'page')->whereIn('id', array_map(fn ($r) => (int) $r['c'], $rows))
            ->with(['translations', 'featured'])->get()->keyBy('id');

        $out = [];
        $used = [];
        foreach ($rows as $r) {
            $c = $contents[(int) $r['c']] ?? null;
            $tr = $c?->tr($locale) ?? ($c?->is_home ? $c->tr(mcms_default_locale()) : null);
            if (! $c || ! $tr) {
                continue;
            }
            $tr->setRelation('content', $c);
            $id = $c->is_home ? 'home' : (string) $tr->slug;
            if ($id === '' || isset($used[$id])) {
                continue;
            }
            $used[$id] = true;
            $a = (isset($r['a']) && isset(self::ANIMS[$r['a']])) ? $r['a'] : 'default';
            $out[] = ['id' => $id, 'content' => $c, 'tr' => $tr, 'anim' => $a === 'default' ? $global : $a, 'label' => (string) $tr->title];
        }

        return $out;
    }

    /** Menu URL -> #anchor (or home#anchor on other pages). */
    public static function href(string $url): string
    {
        if (! self::active()) {
            return $url;
        }
        if (self::$cache === null) {
            self::$cache = [];
            $onHome = request()->routeIs('home');
            $home = rtrim(lroute('home'), '/');
            foreach (self::sections() as $s) {
                $key = $s['content']->is_home ? $home : rtrim($s['tr']->url(), '/');
                self::$cache[$key] = ($onHome ? '' : $home.'/').'#'.$s['id'];
            }
        }

        return self::$cache[rtrim($url, '/')] ?? $url;
    }
}
