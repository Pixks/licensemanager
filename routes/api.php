<?php

declare(strict_types=1);

use App\Controllers\Api\LicenseApiController;
use App\Controllers\Api\UpdateApiController;

$router = $this->router();
$middleware = ['force_https', 'rate:api,120,3600'];
$router->post('/api/v1/licenses/activate', [LicenseApiController::class, 'activate'], $middleware);
$router->post('/api/v1/licenses/deactivate', [LicenseApiController::class, 'deactivate'], $middleware);
$router->post('/api/v1/licenses/check', [LicenseApiController::class, 'check'], $middleware);
$router->post('/api/v1/licenses/heartbeat', [LicenseApiController::class, 'heartbeat'], $middleware);
$router->post('/api/v1/updates/check', [UpdateApiController::class, 'check'], $middleware);
$router->get('/api/v1/updates/download', [UpdateApiController::class, 'download'], ['force_https']);
$router->get('/api/v1/products/{product_slug}/latest', [UpdateApiController::class, 'latest'], ['force_https']);
