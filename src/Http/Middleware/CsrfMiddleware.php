<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application;
use App\Http\Request;
use App\Http\Response;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, Application $app, array $params = []): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }
        $tokenName = (string) $app->config('app.csrf_token_name', '_token');
        $session = &$request->sessionRef();
        $sessionToken = $session[$tokenName] ?? null;
        $submitted = $request->input($tokenName) ?? $request->header('X-CSRF-TOKEN');
        if (!$sessionToken || !$submitted || !hash_equals((string) $sessionToken, (string) $submitted)) {
            return $request->wantsJson() ? Response::json(['success' => false, 'error' => ['code' => 'invalid_csrf', 'message' => 'Invalid CSRF token.']], 419) : Response::html('<h1>419 Invalid CSRF Token</h1>', 419);
        }
        return $next($request);
    }
}
