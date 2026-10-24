<?php

return [
    'version' => '4.2.0',

    // Secret one-time key required by the web installer (written to .env on first boot)
    'install_key' => env('MCMS_INSTALL_KEY'),

    // Admin URL segment. Change it in .env to hide the panel from bots.
    'admin_path' => trim((string) env('MCMS_ADMIN_PATH', 'panel'), '/') ?: 'panel',

    // Language of the admin panel and installer: en | bg (users can switch with the EN/BG links)
    'admin_locale' => env('MCMS_ADMIN_LOCALE', 'en'),

    'idle_minutes' => (int) env('MCMS_ADMIN_IDLE_MINUTES', 30),
    'max_upload_mb' => (int) env('MCMS_MAX_UPLOAD_MB', 8),
    'max_video_mb' => (int) env('MCMS_MAX_VIDEO_MB', 30),   // WebM / MP4 backgrounds (also limited by PHP upload_max_filesize)
    'image_max_px' => 2560,
    'thumb_px' => 480,

    // Slugs that can never be used by content (they are real routes)
    'reserved_slugs' => [
        'blog', 'search', 'contact', 'comment', 'install', 'sitemap', 'robots', 'feed', 'uploads',
        'assets', 'storage', 'vendor', 'up', 'admin', 'panel', 'login', 'logout', 'api',
        'register', 'author', 'newsletter', 'app', 'config', 'database', 'resources', 'routes', 'plugins', 'lang', 'bootstrap', 'tests', 'public',
    ],

    // Role => permissions ('*' = everything)
    'roles' => [
        'admin' => ['*'],
        'editor' => ['content', 'content.all', 'pages', 'publish', 'categories', 'media', 'menus', 'messages', 'comments', 'redirects', 'translations', 'blocks', 'notfound', 'stats'],
        'author' => ['content', 'media', 'publish'],       // writes and publishes own posts
        'contributor' => ['content'],                       // writes own posts; they wait for approval ("pending")
        'subscriber' => [],                                 // registered account: only own profile
    ],

    'role_labels' => [
        'admin' => 'Администратор',
        'editor' => 'Редактор',
        'author' => 'Автор',
        'contributor' => 'Сътрудник',
        'subscriber' => 'Абонат',
    ],

    'embed_hosts' => ['www.youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com'],
];
