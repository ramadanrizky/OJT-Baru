<?php

return [
    'python_api_url' => env('PYTHON_API_URL', 'http://127.0.0.1:5001'),
    'python_bin' => env('PYTHON_BIN', 'python'),
    'bridge_secret' => env('LEGACY_BRIDGE_SECRET', ''),
    'bridge_ttl' => (int) env('LEGACY_BRIDGE_TTL', 300),
    'max_upload_mb' => (int) env('MAX_UPLOAD_MB', 500),

    /*
    |--------------------------------------------------------------------------
    | Status migrasi per modul
    |--------------------------------------------------------------------------
    | "laravel" = ditangani aplikasi ini.
    | "flask"   = diteruskan ke aplikasi Flask lama (rollback).
    */
    'modules' => [
        'auth' => env('MIGRATE_AUTH', 'laravel'),
        'pages' => env('MIGRATE_PAGES', 'laravel'),
        'users' => env('MIGRATE_USERS', 'flask'),
        'struktur' => env('MIGRATE_STRUKTUR', 'flask'),
        'tugas' => env('MIGRATE_TUGAS', 'flask'),
        'disposisi' => env('MIGRATE_DISPOSISI', 'flask'),
        'pengumuman' => env('MIGRATE_PENGUMUMAN', 'flask'),
        'rapat' => env('MIGRATE_RAPAT', 'flask'),
        'bantuan' => env('MIGRATE_BANTUAN', 'flask'),
        'format_surat' => env('MIGRATE_FORMAT_SURAT', 'flask'),
        'dokumen' => env('MIGRATE_DOKUMEN', 'flask'),
    ],
];
