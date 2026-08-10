<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application;
use App\Http\Request;
use App\Http\Response;

final class ForceHttpsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, Application $app, array $params = []): Response
    {
        if ($app->config('app.force_https') && !$request->isSecure() && $app->config('app.env') !== 'testing') {
            $url = preg_replace('#^http://#', 'https://', $app->config('app.url') . $request->path());
            return Response::redirect((string) $url, 301);
        }
        return $next($request);
    }
}
