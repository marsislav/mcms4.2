<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Reusable content blocks and templates (table `blocks`, admin: "Blocks & templates").
 *  - kind "block": inserted anywhere with the shortcode [block my-slug] (content body, HTML widget) or the "Block" widget
 *  - kind "template": starting content offered when creating a new post/page
 * Bodies are sanitised on save. Without the table everything degrades to "no blocks".
 */
class Blocks
{
    public const KINDS = ['block' => 'Блок', 'template' => 'Шаблон'];
    public const APPLIES = ['any' => 'Статии и страници', 'post' => 'Само статии', 'page' => 'Само страници'];

    /** Max nested [block] expansion (a block may contain another block). */
    private const DEPTH = 3;

    /** Pure: replaces every [block slug] using $resolve(slug): ?string. Unknown slugs are removed. */
    public static function expandWith(string $html, callable $resolve, int $depth = 0): string
    {
        if (! str_contains($html, '[block')) {
            return $html;
        }

        return preg_replace_callback('/\[block\s+([a-z0-9\-]{1,80})\s*\]/', function ($m) use ($resolve, $depth) {
            if ($depth >= self::DEPTH) {
                return '';
            }
            $body = $resolve($m[1]);

            return $body === null ? '' : self::expandWith($body, $resolve, $depth + 1);
        }, $html) ?? $html;
    }

    public static function expand(string $html): string
    {
        if (! str_contains($html, '[block') || ! Tables::has('blocks')) {
            return $html;
        }

        return self::expandWith($html, fn ($slug) => self::body($slug));
    }

    /** Body of a block in the current language (fallback: default language body). */
    public static function body(string $slug, ?string $locale = null): ?string
    {
        static $cache = [];
        try {
            $row = $cache[$slug] ??= DB::table('blocks')->where('slug', $slug)->where('kind', 'block')->first(['body', 'i18n']) ?: false;
        } catch (\Throwable) {
            return null;
        }
        if (! $row) {
            return null;
        }
        $locale ??= app()->getLocale();
        $i18n = $row->i18n ? (json_decode($row->i18n, true) ?: []) : [];

        return (string) (($i18n[$locale] ?? '') !== '' ? $i18n[$locale] : $row->body);
    }

    /** Pure: slug from a name ("Contact box" => "contact-box"), made unique against $taken. */
    public static function slugify(string $name, array $taken = []): string
    {
        $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', self::translit($name)), '-'));
        $s = $s === '' ? 'block' : substr($s, 0, 70);
        $base = $s;
        for ($i = 2; in_array($s, $taken, true); $i++) {
            $s = $base.'-'.$i;
        }

        return $s;
    }

    private static function translit(string $t): string
    {
        $map = ['а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sht', 'ъ' => 'a', 'ь' => 'y', 'ю' => 'yu', 'я' => 'ya'];

        return strtr(mb_strtolower($t), $map);
    }

    /** [slug => name] of blocks for pickers. */
    public static function options(): array
    {
        if (! Tables::has('blocks')) {
            return [];
        }
        try {
            return DB::table('blocks')->where('kind', 'block')->orderBy('name')->pluck('name', 'slug')->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /** Templates usable for a content type: id, name, body. */
    public static function templatesFor(string $type): array
    {
        if (! Tables::has('blocks')) {
            return [];
        }
        try {
            return DB::table('blocks')->where('kind', 'template')->whereIn('applies_to', ['any', $type])->orderBy('name')->get(['id', 'name', 'body'])->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
