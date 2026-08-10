<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application;
use App\Http\Request;
use App\Http\Response;

final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, Application $app, array $params = []): Response
    {
        if ($app->auth()->user()) {
            return Response::redirect('/admin');
        }
        return $next($request);
    }
}
