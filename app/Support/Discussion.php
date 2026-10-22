<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\Content;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Comments ("Discussion", modelled on WordPress > Settings > Discussion).
 * Settings are stored as `discussion.<key>`. Pure helpers (decide, format, thread) have no Laravel dependency
 * except setting(), so they can be tested with php tests/standalone.php.
 */
class Discussion
{
    public const DEFAULTS = [
        'enabled' => '1',          // master switch (off = no form, no list, nothing stored)
        'on_posts' => '1',         // allow comments on new posts
        'on_pages' => '0',         // allow comments on new pages
        'require_name_email' => '1',
        'allow_url' => '1',        // "Website" field
        'close_after_days' => '0', // 0 = never
        'threaded' => '1',
        'thread_depth' => '3',
        'per_page' => '20',        // top-level comments per page, 0 = all
        'order' => 'asc',          // asc = older first
        'notify_new' => '1',
        'notify_hold' => '1',
        'moderation_all' => '0',   // every comment must be approved
        'previously_approved' => '1', // author must have an approved comment before
        'max_links' => '2',        // hold when it has this many links or more (0 = off)
        'moderation_keys' => '',   // hold when contains any (one per line)
        'disallowed_keys' => '',   // mark as spam when contains any
        'show_avatars' => '0',     // Gravatar (external request!)
        'flood_seconds' => '15',
    ];

    public const BOOL_KEYS = ['enabled', 'on_posts', 'on_pages', 'require_name_email', 'allow_url', 'threaded',
        'notify_new', 'notify_hold', 'moderation_all', 'previously_approved', 'show_avatars'];

    /* ------------------------------------------------------------ settings */

    public static function get(string $key): string
    {
        $v = function_exists('setting') ? setting('discussion.'.$key) : null;

        return ($v === null || $v === '') ? (self::DEFAULTS[$key] ?? '') : (string) $v;
    }

    public static function all(): array
    {
        $o = [];
        foreach (self::DEFAULTS as $k => $_) {
            $o[$k] = self::get($k);
        }

        return $o;
    }

    public static function on(string $key): bool
    {
        return self::get($key) === '1';
    }

    /** Is the comments migration applied? */
    public static function ready(): bool
    {
        try {
            return Cache::remember('mcms.comments_ready', 60, fn () => Schema::hasTable('comments')
                && Schema::hasColumn('contents', 'comments_open')) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function forget(): void
    {
        Cache::forget('mcms.comments_ready');
    }

    /** Master switch + migration. */
    public static function enabled(): bool
    {
        return self::on('enabled') && self::ready();
    }

    /* ------------------------------------------------------------- policy */

    /** May visitors write new comments on this content right now? */
    public static function isOpen(Content $c): bool
    {
        if (! self::enabled() || $c->type === 'category') {
            return false;
        }
        $explicit = $c->comments_open;
        $open = $explicit !== null ? (bool) $explicit : self::on($c->type === 'post' ? 'on_posts' : 'on_pages');
        if (! $open) {
            return false;
        }
        $days = (int) self::get('close_after_days');
        if ($days > 0 && $c->type === 'post') {
            $since = $c->publish_at ?? $c->created_at;
            if ($since && $since->lt(now()->subDays($days))) {
                return false;
            }
        }

        return true;
    }

    /** Show the comments block (existing comments stay visible after closing)? */
    public static function visible(Content $c): bool
    {
        return self::enabled() && ($c->type === 'post' || $c->type === 'page') && (self::isOpen($c) || self::approvedCount($c) > 0);
    }

    public static function approvedCount(Content $c): int
    {
        static $cache = [];

        return $cache[$c->id] ??= (self::ready() ? Comment::where('content_id', $c->id)->where('status', 'approved')->count() : 0);
    }

    /* --------------------------------------------------------- moderation */

    /**
     * Decide the status of a new comment (WordPress order: disallowed -> moderation rules -> approved).
     * $c: author, email, url, body, ip. $trusted = written by a logged-in editor/admin.
     */
    public static function decide(array $c, array $cfg, bool $hasApprovedBefore, bool $trusted = false): string
    {
        if ($trusted) {
            return 'approved';
        }
        $haystack = mb_strtolower(implode("\n", [$c['author'] ?? '', $c['email'] ?? '', $c['url'] ?? '', $c['body'] ?? '', $c['ip'] ?? '']));

        if (self::matchesAny($haystack, (string) ($cfg['disallowed_keys'] ?? ''))) {
            return 'spam';
        }
        if (($cfg['moderation_all'] ?? '0') === '1') {
            return 'pending';
        }
        $maxLinks = (int) ($cfg['max_links'] ?? 0);
        if ($maxLinks > 0 && self::countLinks((string) ($c['body'] ?? '')) >= $maxLinks) {
            return 'pending';
        }
        if (self::matchesAny($haystack, (string) ($cfg['moderation_keys'] ?? ''))) {
            return 'pending';
        }
        if (($cfg['previously_approved'] ?? '0') === '1' && ! $hasApprovedBefore) {
            return 'pending';
        }

        return 'approved';
    }

    /** One word/phrase/IP per line; case-insensitive substring match. */
    public static function matchesAny(string $lowerHaystack, string $list): bool
    {
        foreach (preg_split('/\R/u', $list) ?: [] as $w) {
            $w = mb_strtolower(trim($w));
            if ($w !== '' && str_contains($lowerHaystack, $w)) {
                return true;
            }
        }

        return false;
    }

    public static function countLinks(string $text): int
    {
        return preg_match_all('~(?:https?://|www\.)[^\s<>"\']+~iu', $text) + preg_match_all('~<a\s~i', $text);
    }

    /* ----------------------------------------------------------- rendering */

    /** Plain text -> safe HTML: escaped, URLs linked (nofollow ugc), line breaks kept. */
    public static function format(string $body): string
    {
        $html = htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = preg_replace_callback('~\b(https?://[^\s<>"\']+)~iu', function ($m) {
            $url = $m[1];
            $tail = '';
            if (preg_match('~[.,;:!?)\]]+$~', $url, $t)) { // keep trailing punctuation outside the link
                $tail = $t[0];
                $url = substr($url, 0, -strlen($tail));
            }
            $label = mb_strlen($url) > 60 ? mb_substr($url, 0, 57).'…' : $url;

            return '<a href="'.$url.'" rel="nofollow ugc noopener noreferrer" target="_blank">'.$label.'</a>'.$tail;
        }, $html);

        return nl2br($html, false);
    }

    public static function avatar(?string $email, int $size = 48): ?string
    {
        if (! self::on('show_avatars') || ! $email) {
            return null;
        }

        return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($email))).'?s='.$size.'&d=mp';
    }

    /**
     * Build a threaded structure from approved comments.
     * @param  iterable<array{id:int,parent_id:?int}>|\Illuminate\Support\Collection $rows  ordered as they should appear
     * @return array<int, array{c:mixed, depth:int, children:array}>  top-level nodes
     */
    public static function tree(iterable $rows, bool $threaded, int $maxDepth): array
    {
        $by = [];
        $children = [];
        foreach ($rows as $r) {
            $by[self::val($r, 'id')] = $r;
        }
        foreach ($rows as $r) {
            $pid = self::val($r, 'parent_id');
            $key = ($threaded && $pid && isset($by[$pid])) ? $pid : 0;
            $children[$key][] = $r;
        }
        $build = function (int $parent, int $depth) use (&$build, &$children, $maxDepth): array {
            $out = [];
            foreach ($children[$parent] ?? [] as $r) {
                $id = self::val($r, 'id');
                // replies deeper than the limit are shown at the last allowed level
                $out[] = ['c' => $r, 'depth' => $depth, 'children' => $depth < $maxDepth ? $build($id, $depth + 1) : []];
                if ($depth >= $maxDepth) {
                    foreach ($build($id, $depth) as $flat) {
                        $out[] = $flat;
                    }
                }
            }

            return $out;
        };

        return $build(0, 1);
    }

    private static function val(mixed $r, string $k): mixed
    {
        return is_array($r) ? ($r[$k] ?? null) : $r->{$k};
    }

    /** Depth (1 = top level) of a comment given an id=>parent map. */
    public static function depthOf(int $id, array $parents): int
    {
        $d = 1;
        $seen = [];
        while (! empty($parents[$id]) && ! isset($seen[$id])) {
            $seen[$id] = true;
            $id = $parents[$id];
            $d++;
        }

        return $d;
    }
}
