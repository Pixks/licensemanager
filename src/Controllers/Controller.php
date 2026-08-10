<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use App\Http\Response;

abstract class Controller
{
    public function __construct(protected readonly Application $app) {}
    protected function view(string $template, array $data = []): Response
    {
        $session = &$_SESSION; $csrfName = (string) $this->app->config('app.csrf_token_name', '_token');
        if (empty($session[$csrfName])) $session[$csrfName] = bin2hex(random_bytes(32));
        $flash = $session['_flash'] ?? []; unset($session['_flash']);
        return Response::html($this->app->view()->render($template, array_merge($data, ['app' => $this->app, 'authUser' => $this->app->auth()->user(), 'authRoles' => $this->app->auth()->roles(), 'csrfTokenName' => $csrfName, 'csrfToken' => $session[$csrfName], 'flash' => $flash])));
    }
    protected function json(array $payload, int $status = 200): Response { return Response::json($payload, $status); }
    protected function redirect(string $to, ?string $message = null, string $type = 'success'): Response
    {
        if ($message !== null) $_SESSION['_flash'][$type] = $message;
        return Response::redirect($to);
    }
}
