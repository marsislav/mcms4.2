<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Registration and author profiles. Works before the database update (profile columns missing):
 * everything profile-related is then simply switched off.
 */
class Members
{
    /** Roles that may be given to people who register themselves. */
    public const SELF_ROLES = ['subscriber', 'contributor', 'author'];

    /** Roles that can have a public author page. */
    public const AUTHOR_ROLES = ['contributor', 'author', 'editor', 'admin'];

    public static function ready(): bool
    {
        try {
            return Cache::remember('mcms.members_ready', 60, fn () => Schema::hasColumn('users', 'username')
                && Schema::hasColumn('users', 'bio') && Schema::hasColumn('users', 'website')) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function forget(): void
    {
        Cache::forget('mcms.members_ready');
    }

    public static function registrationOpen(): bool
    {
        return Options::on('registration') && self::ready();
    }

    public static function defaultRole(): string
    {
        $r = Options::get('default_role');

        return in_array($r, self::SELF_ROLES, true) ? $r : 'subscriber';
    }

    /** "Иван Петров" -> "ivan-petrov"; unique through $exists(string): bool. Max 40 chars, letters/digits/dash. */
    public static function usernameFrom(string $name, callable $exists): string
    {
        $map = ['а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sht', 'ъ' => 'a', 'ь' => 'y', 'ю' => 'yu', 'я' => 'ya'];
        $s = strtr(mb_strtolower(trim($name)), $map);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        $s = trim(substr($s, 0, 32), '-');
        if ($s === '' || in_array($s, Options::RESERVED_BASES, true) || preg_match('/^[a-z]{2}$/', $s)) {
            $s = 'user'.($s !== '' ? '-'.$s : '');
        }
        $base = $s;
        $i = 2;
        while ($exists($s)) {
            $s = substr($base, 0, 36).'-'.$i++;
        }

        return $s;
    }

    /** Public URL of an author page (null when profiles are not available for this user). */
    public static function authorUrl($user, ?string $locale = null): ?string
    {
        if (! $user || ! self::ready() || empty($user->username) || ! in_array($user->role, self::AUTHOR_ROLES, true)) {
            return null;
        }

        return lroute('author', ['username' => $user->username], $locale);
    }
}
