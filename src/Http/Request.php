<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    private array $json = [];

    public function __construct(
        private array $server,
        private array $get,
        private array $post,
        private array $files,
        private array $cookies,
        private array &$session,
        private string $rawBody = ''
    ) {
        $contentType = strtolower((string) ($this->server['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($this->rawBody, true);
            $this->json = is_array($decoded) ? $decoded : [];
        }
    }

    public static function fromGlobals(): self
    {
        $session = &$_SESSION;
        return new self($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE, $session, (string) file_get_contents('php://input'));
    }

    public function method(): string
    {
        $method = strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'POST' && isset($this->post['_method'])) {
            $method = strtoupper((string) $this->post['_method']);
        }
        return $method;
    }

    public function path(): string
    {
        $path = parse_url((string) ($this->server['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        return '/' . ltrim($path, '/');
    }

    public function input(string $key, mixed $default = null): mixed { return $this->all()[$key] ?? $default; }
    public function all(): array { return array_replace_recursive($this->get, $this->post, $this->json); }
    public function query(string $key, mixed $default = null): mixed { return $this->get[$key] ?? $default; }
    public function file(string $key): ?array { return $this->files[$key] ?? null; }
    public function header(string $key, mixed $default = null): mixed
    {
        $lookup = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$lookup] ?? $this->server[strtoupper(str_replace('-', '_', $key))] ?? $default;
    }
    public function ip(): string
    {
        $forwardedFor = trim((string) $this->header('X-Forwarded-For', ''));
        if ($forwardedFor !== '') {
            $parts = array_map('trim', explode(',', $forwardedFor));
            if ($parts[0] !== '') {
                return $parts[0];
            }
        }
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }
    public function userAgent(): string { return (string) ($this->server['HTTP_USER_AGENT'] ?? ''); }
    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') || (($this->server['SERVER_PORT'] ?? null) == 443) || strtolower((string) $this->header('X-Forwarded-Proto', '')) === 'https';
    }
    public function wantsJson(): bool
    {
        $accept = strtolower((string) $this->header('Accept', ''));
        return str_contains($accept, 'application/json') || str_starts_with($this->path(), '/api/');
    }
    public function &sessionRef(): array { return $this->session; }
}
