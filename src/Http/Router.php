<?php

declare(strict_types=1);

namespace App\Http;

use App\Application;

final class Router
{
    private array $routes = [];
    public function add(string $method, string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->routes[] = ['method' => strtoupper($method), 'pattern' => $pattern, 'handler' => $handler, 'middleware' => $middleware];
    }
    public function get(string $pattern, callable|array $handler, array $middleware = []): void { $this->add('GET', $pattern, $handler, $middleware); }
    public function post(string $pattern, callable|array $handler, array $middleware = []): void { $this->add('POST', $pattern, $handler, $middleware); }
    public function dispatch(Request $request, Application $app): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) continue;
            $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']) . '$#';
            if (!preg_match($regex, $request->path(), $matches)) continue;
            $params = array_filter($matches, static fn ($key): bool => !is_int($key), ARRAY_FILTER_USE_KEY);
            $core = function (Request $request) use ($route, $app, $params): Response {
                $handler = $route['handler'];
                if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
                    $controller = new $handler[0]($app);
                    return $controller->{$handler[1]}($request, $params);
                }
                return $handler($request, $app, $params);
            };
            $pipeline = array_reduce(array_reverse($route['middleware']), function (callable $next, string $middleware) use ($app, $params): callable {
                return fn (Request $request): Response => $app->middleware($middleware)->handle($request, $next, $app, $params);
            }, $core);
            return $pipeline($request);
        }
        return $request->wantsJson() ? Response::json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Route not found.']], 404) : Response::html('<h1>404 Not Found</h1>', 404);
    }
}
