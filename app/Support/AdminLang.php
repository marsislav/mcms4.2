<?php

namespace App\Support;

/**
 * Language of the admin panel / installer (English by default, Bulgarian optional).
 * Chosen with ?lang=en|bg (remembered in a cookie) or MCMS_ADMIN_LOCALE in .env.
 * Texts are Laravel JSON translations: the Bulgarian text is the key, lang/en.json holds English.
 */
class AdminLang
{
    public const LANGS = ['en' => 'English', 'bg' => 'Български'];
    public const COOKIE = 'mcms_admin_lang';

    public static function valid(?string $code): bool
    {
        return is_string($code) && isset(self::LANGS[$code]);
    }

    /** $query / $cookie: raw values from the request. */
    public static function pick(?string $query, ?string $cookie, ?string $default): string
    {
        foreach ([$query, $cookie, $default] as $c) {
            if (self::valid($c)) {
                return $c;
            }
        }

        return 'en';
    }
}
