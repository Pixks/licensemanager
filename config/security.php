<?php

declare(strict_types=1);

return [
    'app_key' => (string) env('APP_KEY', 'development-key'),
    'download_token_ttl_minutes' => (int) env('DOWNLOAD_TOKEN_TTL_MINUTES', 15),
    'download_token_single_use' => filter_var(env('DOWNLOAD_TOKEN_SINGLE_USE', true), FILTER_VALIDATE_BOOL),
    'allow_localhost' => filter_var(env('ALLOW_LOCALHOST', true), FILTER_VALIDATE_BOOL),
    'allow_private_tlds' => filter_var(env('ALLOW_PRIVATE_TLDS', true), FILTER_VALIDATE_BOOL),
    'allow_staging_keywords' => array_filter(array_map('trim', explode(',', (string) env('ALLOW_STAGING_KEYWORDS', 'staging,stage,dev,test,local')))),
    'api_rate_limit' => (int) env('API_RATE_LIMIT', 120),
    'api_rate_window' => (int) env('API_RATE_WINDOW', 3600),
    'login_rate_limit' => (int) env('LOGIN_RATE_LIMIT', 5),
    'login_rate_window' => (int) env('LOGIN_RATE_WINDOW', 900),
    'log_retention_days' => (int) env('LOG_RETENTION_DAYS', 90),
    'audit_retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
    'cron_secret' => (string) env('CRON_SECRET', ''),
];
