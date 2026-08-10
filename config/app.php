<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'LicenseManager'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/'),
    'force_https' => filter_var(env('APP_FORCE_HTTPS', true), FILTER_VALIDATE_BOOL),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'pl_PL'),
    'session_name' => env('SESSION_NAME', 'licensemanager_session'),
    'csrf_token_name' => env('CSRF_TOKEN_NAME', '_token'),
    'upload_max_bytes' => (int) env('UPLOAD_MAX_BYTES', 52428800),
    'upload_storage_path' => env('UPLOAD_STORAGE_PATH', ''),
    'grace_period_days' => (int) env('GRACE_PERIOD_DAYS', 10),
    'mail_from_address' => env('MAIL_FROM_ADDRESS', ''),
    'mail_from_name' => env('MAIL_FROM_NAME', 'LicenseManager'),
];
