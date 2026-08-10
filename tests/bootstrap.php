<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/functions.php';
$_ENV['APP_ENV'] = 'testing'; $_SERVER['APP_ENV'] = 'testing'; $_ENV['APP_DEBUG'] = 'true'; $_ENV['APP_FORCE_HTTPS'] = 'false'; $_ENV['APP_KEY'] = 'testing-app-key-123456789'; $_ENV['DB_DRIVER'] = 'sqlite'; $_ENV['DB_DATABASE'] = base_path('database/testing.sqlite'); $_ENV['DOWNLOAD_TOKEN_SINGLE_USE'] = 'true'; $_ENV['GRACE_PERIOD_DAYS'] = '10'; $_ENV['ALLOW_LOCALHOST'] = 'true'; $_ENV['ALLOW_STAGING_KEYWORDS'] = 'staging,dev,test,local'; $_SERVER = array_merge($_SERVER, $_ENV);
require base_path('vendor/autoload.php');
