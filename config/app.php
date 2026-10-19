<?php

return [
    'name' => env('APP_NAME', 'mCMS'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Europe/Sofia',
    'locale' => env('APP_LOCALE', 'bg'),
    // Framework strings (validation etc.) fall back to English; site UI strings use the DB (t()).
    'fallback_locale' => 'bg', // Bulgarian texts are the translation keys, so "fallback" returns the key itself
    'faker_locale' => 'bg_BG',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [],
    'maintenance' => ['driver' => 'file'],
];
