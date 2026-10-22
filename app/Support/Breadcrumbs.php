<?php

namespace App\Support;

/**
 * Breadcrumb trail helpers. A crumb is ['label' => string, 'url' => ?string]; the last one is the current page (url = null).
 * Display options live in Settings > SEO & navigation (opt.breadcrumbs, opt.bc_*).
 */
class Breadcrumbs
{
    /** Whether the trail should be shown for a page kind: page | post | list. */
    public static function visible(string $kind): bool
    {
        if (! Options::on('breadcrumbs')) {
            return false;
        }

        return match ($kind) {
            'page' => Options::on('bc_on_pages'),
            'post' => Options::on('bc_on_posts'),
            default => Options::on('bc_on_lists'),
        };
    }

    /** Prepends "Home"; drops crumbs with an empty label. $trail: crumbs after Home. */
    public static function make(array $trail, string $homeLabel, string $homeUrl): array
    {
        $out = [['label' => $homeLabel, 'url' => $homeUrl]];
        foreach ($trail as $c) {
            if (trim((string) ($c['label'] ?? '')) !== '') {
                $out[] = ['label' => trim((string) $c['label']), 'url' => $c['url'] ?? null];
            }
        }
        // the last crumb is always the current page
        $out[count($out) - 1]['url'] = null;

        return count($out) > 1 ? $out : [];
    }

    /** Applies "show the current page" (off = the last crumb is dropped). */
    public static function forDisplay(array $crumbs): array
    {
        if ($crumbs && ! Options::on('bc_show_current')) {
            array_pop($crumbs);
            // a single "Home" crumb is pointless
            if (count($crumbs) < 2 && count($crumbs) > 0) {
                return $crumbs;
            }
        }

        return $crumbs;
    }
}
