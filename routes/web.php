<?php

declare(strict_types=1);

use App\Controllers\Admin\ActivationController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\LicenseController;
use App\Controllers\Admin\LogController;
use App\Controllers\Admin\ProductController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\UserController;
use App\Controllers\AuthController;
use App\Controllers\CronController;

$router = $this->router();
$router->get('/', static fn ($request, $app) => \App\Http\Response::redirect('/admin'), ['force_https']);
$router->get('/login', [AuthController::class, 'showLogin'], ['guest', 'force_https']);
$router->post('/login', [AuthController::class, 'login'], ['guest', 'force_https', 'csrf', 'rate:login,5,900']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth', 'csrf']);
$router->get('/cron/run', [CronController::class, 'run']);
$admin = ['auth', 'force_https']; $write = ['auth', 'force_https', 'csrf', 'role:superadmin,admin'];
$router->get('/admin', [DashboardController::class, 'index'], $admin);
$router->get('/admin/products', [ProductController::class, 'index'], $admin);
$router->get('/admin/products/create', [ProductController::class, 'create'], $write);
$router->post('/admin/products', [ProductController::class, 'store'], $write);
$router->get('/admin/products/{id}', [ProductController::class, 'show'], $admin);
$router->post('/admin/products/{id}', [ProductController::class, 'update'], $write);
$router->post('/admin/products/{id}/versions', [ProductController::class, 'storeVersion'], $write);
$router->get('/admin/licenses', [LicenseController::class, 'index'], $admin);
$router->get('/admin/licenses/create', [LicenseController::class, 'create'], $write);
$router->post('/admin/licenses', [LicenseController::class, 'store'], $write);
$router->get('/admin/licenses/export', [LicenseController::class, 'exportCsv'], $admin);
$router->get('/admin/licenses/{id}', [LicenseController::class, 'show'], $admin);
$router->post('/admin/licenses/{id}', [LicenseController::class, 'updateStatus'], $write);
$router->post('/admin/licenses/{id}/domain-rules', [LicenseController::class, 'addDomainRule'], $write);
$router->get('/admin/activations', [ActivationController::class, 'index'], $admin);
$router->post('/admin/activations/{id}/release', [ActivationController::class, 'release'], $write);
$router->get('/admin/logs', [LogController::class, 'index'], $admin);
$router->get('/admin/users', [UserController::class, 'index'], ['auth', 'force_https', 'role:superadmin']);
$router->post('/admin/users', [UserController::class, 'store'], ['auth', 'force_https', 'csrf', 'role:superadmin']);
$router->get('/admin/settings', [SettingsController::class, 'edit'], ['auth', 'force_https', 'role:superadmin,admin']);
$router->post('/admin/settings', [SettingsController::class, 'update'], ['auth', 'force_https', 'csrf', 'role:superadmin,admin']);
