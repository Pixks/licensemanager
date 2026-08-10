<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;

final class CronController extends Controller
{
    public function run(Request $request): Response
    {
        $secret = (string) $this->app->config('security.cron_secret', '');
        if ($secret === '' || !hash_equals($secret, (string) $request->query('secret', ''))) return $this->json(['success' => false, 'error' => ['code' => 'forbidden', 'message' => 'Invalid cron secret.']], 403);
        return $this->json(['success' => true, 'data' => $this->app->maintenanceService()->runAll()]);
    }
}
