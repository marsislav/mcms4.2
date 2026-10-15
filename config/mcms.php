<?php

return [
    'version' => '4.2.0',

    // Secret one-time key required by the web installer (written to .env on first boot)
    'install_key' => env('MCMS_INSTALL_KEY'),

    // Admin URL segment. Change it in .env to hide the panel from bots.
    'admin_path' => trim((string) env('MCMS_ADMIN_PATH', 'panel'), '/') ?: 'panel',

    'idle_minutes' => (int) env('MCMS_ADMIN_IDLE_MINUTES', 30),
    'max_upload_mb' => (int) env('MCMS_MAX_UPLOAD_MB', 8),
    'image_max_px' => 2560,
    'thumb_px' => 480,

    // Slugs that can never be used by content (they are real routes)
    'reserved_slugs' => [
        'blog', 'search', 'contact', 'install', 'sitemap', 'robots', 'feed', 'uploads',
        'assets', 'storage', 'vendor', 'up', 'admin', 'panel', 'login', 'logout', 'api',
        'app', 'config', 'database', 'resources', 'routes', 'plugins', 'lang', 'bootstrap', 'tests', 'public',
    ],

    // Role => permissions ('*' = everything)
    'roles' => [
        'admin' => ['*'],
        'editor' => ['content', 'content.all', 'categories', 'media', 'menus', 'messages', 'redirects', 'translations'],
        'author' => ['content', 'media'],
    ],

    'role_labels' => [
        'admin' => 'Администратор',
        'editor' => 'Редактор',
        'author' => 'Автор',
    ],

    'embed_hosts' => ['www.youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com'],
];
