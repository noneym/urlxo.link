<?php

// Sign-in hub served on its own host (auth.urlxo.link). One Google OAuth client
// with one fixed redirect URI serves every registered site; sites are rows in
// auth_sites, so adding or moving a site never touches Google.
return [
    'domain' => env('AUTH_HUB_DOMAIN', 'auth.urlxo.link'),

    'google' => [
        'client_id' => env('AUTH_HUB_GOOGLE_CLIENT_ID'),
        'client_secret' => env('AUTH_HUB_GOOGLE_CLIENT_SECRET'),
        // Must match the client's authorized redirect URI exactly.
        'redirect' => env('AUTH_HUB_GOOGLE_REDIRECT', 'https://'.env('AUTH_HUB_DOMAIN', 'auth.urlxo.link').'/callback'),
    ],

    // Lifetime of the signed token handed to a site, in seconds.
    'token_ttl' => (int) env('AUTH_HUB_TOKEN_TTL', 120),

    // Lifetime of a sign-in attempt (start to Google callback), in seconds.
    'state_ttl' => (int) env('AUTH_HUB_STATE_TTL', 900),

    // Bearer token for the site registry API (/api/hub/sites). Empty disables it.
    'admin_token' => env('AUTH_HUB_ADMIN_TOKEN'),

    // Enables /selftest, a built-in consumer for the `selftest` site.
    'selftest' => (bool) env('AUTH_HUB_SELFTEST', false),
];
