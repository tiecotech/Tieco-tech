<?php

return [
    'locales' => ['en', 'ar'],
    'default_locale' => 'en',

    // Skips the coming-soon gate regardless of the dashboard switch (tests, emergencies).
    'force_live' => (bool) env('SITE_FORCE_LIVE', false),

    // Secret for the post-deploy hook (FTP deploys only). Empty = hook disabled. Min 32 chars.
    'deploy_token' => env('DEPLOY_TOKEN'),

    // Private disk for customer attachments (never served directly).
    'uploads' => [
        'disk' => env('TIECO_UPLOAD_DISK', 'local'),
        'max_files' => 5,
        'max_kb' => 10240,
        'mimes' => ['pdf', 'docx', 'doc', 'png', 'jpg', 'jpeg', 'webp', 'txt', 'zip'],
    ],

    // Admin bootstrap (read by AdminUserSeeder only; no defaults on purpose).
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
