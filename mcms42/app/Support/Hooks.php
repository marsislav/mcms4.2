<?php

namespace App\Support;

/**
 * Minimal action/filter registry used by the plugin system.
 *
 * Actions : head, footer, content.saved, contact.received, user.login ...
 * Filters : content.body, admin.menu, site.menu, ...
 */
class Hooks
{
    /** @var array<string, array<int, array<int, callable>>> */
    private static array $actions = [];

    /** @var array<string, array<int, array<int, callable>>> */
    private static array $filters = [];

    public static function addAction(string $tag, callable $cb, int $priority = 10): void
    {
        self::$actions[$tag][$priority][] = $cb;
    }

    public static function addFilter(string $tag, callable $cb, int $priority = 10): void
    {
        self::$filters[$tag][$priority][] = $cb;
    }

    public static function doAction(string $tag, mixed ...$args): void
    {
        foreach (self::sorted(self::$actions[$tag] ?? []) as $cb) {
            try {
                $cb(...$args);
            } catch (\Throwable $e) {
                report($e); // a faulty plugin must never take the site down
            }
        }
    }

    public static function applyFilters(string $tag, mixed $value, mixed ...$args): mixed
    {
        foreach (self::sorted(self::$filters[$tag] ?? []) as $cb) {
            try {
                $value = $cb($value, ...$args);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $value;
    }

    public static function has(string $tag): bool
    {
        return isset(self::$actions[$tag]) || isset(self::$filters[$tag]);
    }

    private static function sorted(array $byPriority): array
    {
        ksort($byPriority);

        return $byPriority ? array_merge(...array_values($byPriority)) : [];
    }
}
