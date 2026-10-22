<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Cached "does this optional table exist" check. New features degrade gracefully (and show an
 * "update the database" hint) until the migration has been run on an existing install.
 */
class Tables
{
    public const OPTIONAL = ['page_views', 'not_found_log', 'subscribers', 'blocks'];

    public static function has(string $table): bool
    {
        return Cache::remember('mcms.table.'.$table, 60, function () use ($table) {
            try {
                return Schema::hasTable($table);
            } catch (\Throwable) {
                return false;
            }
        }) === true;
    }

    public static function forget(): void
    {
        foreach (self::OPTIONAL as $t) {
            Cache::forget('mcms.table.'.$t);
        }
    }
}
