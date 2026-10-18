<?php

return [
    // Host the short links are served from (no scheme).
    'short_domain' => env('SHORT_DOMAIN', 'urlxo.link'),

    // Public source repository, linked from the site.
    'github_url' => env('URLXO_GITHUB_URL', 'https://github.com/noneym/urlxo.link'),

    'code_length' => (int) env('URLXO_CODE_LENGTH', 6),

    // Resolved link payloads are cached in Redis for this many seconds.
    'cache_ttl' => (int) env('URLXO_CACHE_TTL', 3600),

    'registration_enabled' => (bool) env('URLXO_REGISTRATION', true),

    // Anonymous shortening on the landing page.
    'guest' => [
        'enabled' => (bool) env('URLXO_GUEST_SHORTEN', true),
        'per_hour' => (int) env('URLXO_GUEST_PER_HOUR', 20),
    ],

    'api' => [
        'per_minute' => (int) env('URLXO_API_PER_MINUTE', 120),
    ],

    // Codes that would collide with app routes.
    'reserved' => [
        'api', 'app', 'login', 'logout', 'register', 'password', 'forgot-password', 'reset-password',
        'docs', 'legal', 'terms', 'privacy', 'up', 'build', 'livewire', 'storage', 'admin', 'dashboard',
        'favicon', 'robots', 'sitemap', 'qr', 'unlock', 'about', 'pricing', 'help', 'support', 'abuse',
        'status', 'static', 'assets', 'www', 'mail', 'urlxo',
    ],

    // Destination hosts that can never be shortened (exact match or subdomain).
    'blocked_hosts' => array_filter(array_map('trim', explode(',', (string) env('URLXO_BLOCKED_HOSTS', '')))),
];
