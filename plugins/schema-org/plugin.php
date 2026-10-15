<?php

/**
 * Example plugin: emits JSON-LD in <head>.
 * Demonstrates the hook API: add_action('head', ...), add_action('content.saved', ...).
 */

use Illuminate\Support\Facades\Cache;

add_action('head', function () {
    $site = setting('site_name', config('app.name'));

    $graph = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#org',
                'name' => $site,
                'url' => url('/'),
            ],
            [
                '@type' => 'WebSite',
                'url' => url('/'),
                'name' => $site,
                'inLanguage' => app()->getLocale(),
                'publisher' => ['@id' => url('/').'#org'],
            ],
        ],
    ];

    // JSON_HEX_* flags make the payload safe inside an HTML <script> element
    echo '<script type="application/ld+json" nonce="'.e(csp_nonce()).'">'
        .json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        ."</script>\n";
});

// Keep the sitemap fresh as soon as content changes
add_action('content.saved', fn () => Cache::forget('mcms.sitemap'));
