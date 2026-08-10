<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application;
use App\Http\Request;
use App\Http\Response;

final class RoleMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly array $roles) {}
    public function handle(Request $request, callable $next, Application $app, array $params = []): Response
    {
        if (!$app->auth()->hasRole($this->roles)) {
            return $request->wantsJson() ? Response::json(['success' => false, 'error' => ['code' => 'forbidden', 'message' => 'Insufficient permissions.']], 403) : Response::html('<h1>403 Forbidden</h1>', 403);
        }
        return $next($request);
    }
}
