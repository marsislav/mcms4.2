<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** Is the soft-delete ("кошче") migration applied? Until then models behave as before (hard delete). */
class Trash
{
    public static function ready(): bool
    {
        try {
            return Cache::remember('mcms.trash_ready', 60, fn () => Schema::hasColumn('contents', 'deleted_at')
                && Schema::hasColumn('categories', 'deleted_at')) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function forget(): void
    {
        Cache::forget('mcms.trash_ready');
    }
}
