<?php

namespace App\Support;

use App\Models\Content;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * IndexNow (Bing, Yandex, Seznam, Naver ...): tells search engines about new, changed and deleted pages within minutes.
 * Google does not use IndexNow; keep the sitemap for it.
 *
 * Protocol: POST https://api.indexnow.org/IndexNow {host, key, keyLocation, urlList}; the key must also be served as a text file at
 * https://<host>/<key>.txt (route in routes/web.php). Submissions run after the response was sent, never block saving and never throw.
 * Settings > SEO > IndexNow.
 */
class IndexNow
{
    public const ENDPOINT = 'https://api.indexnow.org/IndexNow';
    public const MAX_URLS = 10000;   // protocol limit per request
    public const CHUNK = 1000;       // URLs per request when submitting everything

    /** Key: 32 hex characters, created on first use, kept in the settings table. */
    public static function key(): string
    {
        $k = (string) setting('indexnow.key', '');
        if (! self::validKey($k)) {
            $k = bin2hex(random_bytes(16));
            Setting::putMany(['indexnow.key' => $k]);
        }

        return $k;
    }

    public static function keyUrl(): string
    {
        return url(self::key().'.txt');
    }

    /** IndexNow accepts 8–128 characters: letters, digits and dashes. */
    public static function validKey(string $k): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9-]{8,128}$/', $k);
    }

    /** Only public production sites are submitted (not localhost, IPs or noindex sites). */
    public static function enabled(): bool
    {
        if (! Options::on('indexnow_on') || Options::on('noindex')) {
            return false;
        }

        return self::publicHost((string) parse_url(url('/'), PHP_URL_HOST));
    }

    public static function publicHost(string $host): bool
    {
        $host = strtolower($host);

        return $host !== '' && str_contains($host, '.') && ! filter_var($host, FILTER_VALIDATE_IP) && ! preg_match('/\.(local|test|localhost|invalid|example)$/', $host);
    }

    /** Request body. Keeps only http(s) URLs of $host, removes duplicates, caps the list. */
    public static function payload(string $host, string $key, array $urls): array
    {
        $clean = [];
        foreach ($urls as $u) {
            $p = is_string($u) ? parse_url($u) : false;
            if ($p && isset($p['scheme'], $p['host']) && in_array($p['scheme'], ['http', 'https'], true) && strcasecmp($p['host'], $host) === 0) {
                $clean[$u] = true;
            }
        }

        return ['host' => $host, 'key' => $key, 'keyLocation' => 'https://'.$host.'/'.$key.'.txt', 'urlList' => array_slice(array_keys($clean), 0, self::MAX_URLS)];
    }

    /** Public URLs of one content item (every language), or [] when it is not publicly indexable. */
    public static function urlsFor(Content $content, bool $evenIfHidden = false): array
    {
        if (! $evenIfHidden && (! $content->isVisible() || $content->noindex)) {
            return [];
        }
        $content->loadMissing('translations');
        $locales = mcms_locales();
        $out = [];
        foreach ($content->translations as $t) {
            if (in_array($t->locale, $locales, true)) {
                $t->setRelation('content', $content);
                $out[] = $t->url();
            }
        }

        return $out;
    }

    /** Queue the URLs of a saved (or deleted) item; sent after the response. */
    public static function notify(Content $content, bool $deleted = false): void
    {
        try {
            if (! self::enabled()) {
                return;
            }
            if (! $deleted) {
                $content->refresh(); // the controller's copy can be stale after saving
            }
            $urls = self::urlsFor($content, $deleted);
            if ($content->type === 'post') {
                foreach (mcms_locales() as $l) {
                    $urls[] = lroute('blog', [], $l);
                }
            }
            self::queue($urls);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function queue(array $urls): void
    {
        // the same URL is not sent twice within 10 minutes (repeated saves)
        $fresh = array_values(array_filter(array_unique($urls), fn ($u) => Cache::add('indexnow.'.md5($u), 1, 600)));
        if ($fresh) {
            app()->terminating(fn () => self::send($fresh));
        }
    }

    /** Sends one request and stores the outcome for the settings page. Returns the HTTP status (0 = network error). */
    public static function send(array $urls): int
    {
        $host = (string) parse_url(url('/'), PHP_URL_HOST);
        $body = self::payload($host, self::key(), $urls);
        if (! $body['urlList']) {
            return 0;
        }
        $status = 0;
        try {
            $status = Http::timeout(6)->acceptJson()->asJson()->post(self::ENDPOINT, $body)->status();
        } catch (\Throwable $e) {
            $status = 0;
        }
        try {
            Setting::putMany(['indexnow.last' => json_encode(['at' => now()->toDateTimeString(), 'status' => $status, 'count' => count($body['urlList'])])]);
        } catch (\Throwable) {
            // ignore
        }

        return $status;
    }

    /** All public, indexable URLs (like the sitemap) submitted in chunks. Returns ['urls' => n, 'status' => last HTTP status]. */
    public static function submitAll(): array
    {
        $urls = [];
        foreach (mcms_locales() as $l) {
            $urls[] = lroute('home', [], $l);
            $urls[] = lroute('blog', [], $l);
        }
        Content::visible()->where('noindex', false)->with('translations')->orderBy('id')->chunk(200, function ($chunk) use (&$urls) {
            foreach ($chunk as $c) {
                array_push($urls, ...self::urlsFor($c));
            }
        });
        $urls = array_values(array_unique($urls));
        $status = 0;
        foreach (array_chunk($urls, self::CHUNK) as $part) {
            $status = self::send($part);
            if ($status === 0 || $status >= 400) {
                break;
            }
        }

        return ['urls' => count($urls), 'status' => $status];
    }

    /** Last outcome for the settings page: ['at' =>, 'status' =>, 'count' =>] or null. */
    public static function last(): ?array
    {
        $j = json_decode((string) setting('indexnow.last', ''), true);

        return is_array($j) ? $j : null;
    }

    public static function statusText(int $s): string
    {
        return match (true) {
            $s === 200 || $s === 202 => __('Accepted'),
            $s === 0 => __('No connection'),
            $s === 400 => __('Bad request'),
            $s === 403 => __('Key not accepted (check that the key file is reachable)'),
            $s === 422 => __('URLs do not belong to this site'),
            $s === 429 => __('Too many requests, try later'),
            default => __('Error').' '.$s,
        };
    }
}
