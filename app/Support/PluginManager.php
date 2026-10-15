<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Plugins live in /plugins/<slug>/ with plugin.json + plugin.php.
 * They are installed via FTP only (never uploaded through the web panel),
 * which removes the biggest RCE vector of typical CMS plugin systems.
 */
class PluginManager
{
    public static function dir(): string
    {
        return base_path('plugins');
    }

    public static function validSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9\-]{0,60}$/', $slug);
    }

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $enabled = self::enabled();
        $list = [];

        foreach (glob(self::dir().'/*/plugin.json') ?: [] as $file) {
            $slug = basename(dirname($file));
            if (! self::validSlug($slug)) {
                continue;
            }
            $meta = json_decode((string) file_get_contents($file), true);
            if (! is_array($meta)) {
                continue;
            }
            $list[$slug] = [
                'slug' => $slug,
                'name' => (string) ($meta['name'] ?? $slug),
                'version' => (string) ($meta['version'] ?? '1.0.0'),
                'description' => (string) ($meta['description'] ?? ''),
                'author' => (string) ($meta['author'] ?? ''),
                'enabled' => in_array($slug, $enabled, true),
                'has_entry' => is_file(dirname($file).'/plugin.php'),
            ];
        }

        ksort($list);

        return $list;
    }

    /** @return string[] */
    public static function enabled(): array
    {
        $raw = Setting::get('plugins_enabled');
        $arr = $raw ? json_decode($raw, true) : [];

        return is_array($arr) ? array_values(array_filter($arr, 'is_string')) : [];
    }

    public static function setEnabled(array $slugs): void
    {
        Setting::put('plugins_enabled', json_encode(array_values(array_unique($slugs))));
    }

    public static function boot(): void
    {
        foreach (self::enabled() as $slug) {
            if (! self::validSlug($slug)) {
                continue;
            }
            $entry = self::dir().'/'.$slug.'/plugin.php';
            if (! is_file($entry)) {
                continue;
            }
            try {
                (static function (string $__file) {
                    require_once $__file;
                })($entry);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
