<?php

return [
    // Admin area (add / edit / delete). Generate the hash with:
    //   php -r "echo password_hash('your-long-password', PASSWORD_BCRYPT), PHP_EOL;"
    'admin_user' => env('ADMIN_USER'),
    'admin_password_hash' => env('ADMIN_PASSWORD_HASH'),

    // Basic auth sends the password with every request - never allow it over plain HTTP in production.
    'admin_require_https' => (bool) env('ADMIN_REQUIRE_HTTPS', true),

    // The address search engines should treat as THE site (canonical URLs, sitemap, share previews).
    // Today: http://18.138.235.17   Later, if you buy a domain: just change SITE_URL, no code change.
    'site_url' => env('SITE_URL', env('APP_URL', 'http://localhost')),

    // What a title can be (the value is stored in movies.type).
    'types' => [
        'movie' => 'Movie',
        'series' => 'Drama / Series',
        'anime' => 'Anime',
    ],

    // Where posters/trailers are kept: "public" (this server) or "r2" (Cloudflare R2, see config/filesystems.php).
    'media_disk' => env('MEDIA_DISK', 'public'),
    // Public address of the R2 bucket, e.g. https://pub-xxxx.r2.dev (no trailing slash).
    'media_url' => env('R2_PUBLIC_URL'),

    // Languages shown when you hover Movies / Drama / Anime in the navbar, and in the add/edit form.
    'languages' => [
        'Hindi', 'Tamil', 'Malayalam', 'Telugu', 'Kannada', 'English', 'Sinhala', 'Korean', 'Japanese', 'Chinese', 'Other',
    ],

    // One-click keyword suggestions under the "Keywords" box of the add/edit form (you can still type any keyword).
    'quick_tags' => [
        'sinhala sub', 'romantic', 'family', 'dubbed', 'new release', 'top rated', 'classic', '2026', '2025', '2024', '2023',
    ],

    // Genres shown in the navbar dropdown and the add/edit form.
    'genres' => [
        'Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Documentary', 'Drama', 'Family',
        'Fantasy', 'Horror', 'Mystery', 'Romance', 'Sci-Fi', 'Thriller', 'War',
    ],

    // Sites a "Watch now" player may be embedded from. Anything else is rejected when saving a title.
    // To add a host, add it here AND to frame-src in docker/nginx.conf.
    'embed_hosts' => [
        'www.youtube.com',
        'www.youtube-nocookie.com',
        'player.vimeo.com',
        'iframe.mediadelivery.net',
        '*.cloudflarestream.com',
    ],
];
