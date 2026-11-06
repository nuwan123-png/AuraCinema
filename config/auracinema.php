<?php

return [
    // Admin area (add / edit / delete). Generate the hash with:
    //   php -r "echo password_hash('your-long-password', PASSWORD_BCRYPT), PHP_EOL;"
    'admin_user' => env('ADMIN_USER'),
    'admin_password_hash' => env('ADMIN_PASSWORD_HASH'),

    // Basic auth sends the password with every request - never allow it over plain HTTP in production.
    'admin_require_https' => (bool) env('ADMIN_REQUIRE_HTTPS', true),
];
