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

    // Genres shown in the navbar dropdown and the add/edit form.
    'genres' => [
        'Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Documentary', 'Drama', 'Family',
        'Fantasy', 'Horror', 'Mystery', 'Romance', 'Sci-Fi', 'Thriller', 'War',
    ],
];
