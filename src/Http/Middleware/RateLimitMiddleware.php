<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application;
use App\Http\Request;
use App\Http\Response;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $bucket, private readonly int $limit, private readonly int $window) {}
    public function handle(Request $request, callable $next, Application $app, array $params = []): Response
    {
        $key = $this->bucket . ':' . sha1($request->ip() . '|' . $request->path());
        if (!$app->rateLimiter()->attempt($key, $this->limit, $this->window)) {
            $app->securityService()->log('rate_limit_hit', ['bucket' => $this->bucket, 'path' => $request->path(), 'ip' => $request->ip()]);
            return Response::json(['success' => false, 'error' => ['code' => 'rate_limited', 'message' => 'Too many requests. Try again later.']], 429, ['Retry-After' => (string) $this->window]);
        }
        return $next($request);
    }
}
