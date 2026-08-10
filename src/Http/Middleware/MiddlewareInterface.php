<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application;
use App\Http\Request;
use App\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next, Application $app, array $params = []): Response;
}
