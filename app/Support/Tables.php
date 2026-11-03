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
    public const OPTIONAL = ['page_views', 'not_found_log', 'subscribers', 'blocks', 'visits', 'search_terms', 'click_log'];

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

    /** Cached "does this column exist" check (used to detect an unfinished upgrade of an existing table). */
    public static function column(string $table, string $column): bool
    {
        return Cache::remember('mcms.column.'.$table.'.'.$column, 60, function () use ($table, $column) {
            try {
                return Schema::hasColumn($table, $column);
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
        foreach (['source', 'seconds'] as $c) {
            Cache::forget('mcms.column.visits.'.$c);
        }
    }
}
