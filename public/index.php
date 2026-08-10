<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/functions.php';
$app = require base_path('bootstrap/app.php');
session_name((string) $app->config('app.session_name', 'licensemanager_session'));
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$app->run();
