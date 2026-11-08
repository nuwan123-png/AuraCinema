<?php

return [
    'admin_user' => env('ADMIN_USER', 'admin'),
    'admin_password_hash' => env('ADMIN_PASSWORD_HASH'),

    'genres' => [
        'Action',
        'Adventure',
        'Animation',
        'Comedy',
        'Crime',
        'Documentary',
        'Drama',
        'Family',
        'Fantasy',
        'History',
        'Horror',
        'Music',
        'Mystery',
        'Romance',
        'Sci-Fi',
        'Thriller',
        'War',
        'Western',
    ],

    'allowed_embed_hosts' => [
        'youtube.com',
        'www.youtube.com',
        'youtu.be',
        'vimeo.com',
        'player.vimeo.com',
        'iframe.mediadelivery.net',
        'cloudflarestream.com',
        'watch.cloudflarestream.com',
        'streamtape.com',
        'www.streamtape.com',
    ],
];