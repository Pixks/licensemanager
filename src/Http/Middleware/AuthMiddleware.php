<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application;
use App\Http\Request;
use App\Http\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, Application $app, array $params = []): Response
    {
        if (!$app->auth()->user()) {
            return $request->wantsJson() ? Response::json(['success' => false, 'error' => ['code' => 'unauthenticated', 'message' => 'Authentication required.']], 401) : Response::redirect('/login');
        }
        return $next($request);
    }
}
