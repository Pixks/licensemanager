<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Request;
use App\Http\Response;
use App\Models\ApiRequestLog;
use PDO;

final class ApiLogService
{
    public function __construct(private readonly PDO $pdo) {}
    public function log(Request $request, Response $response, array $context = []): void
    {
        $payload = $request->all();
        if (isset($payload['license_key'])) $payload['license_key'] = '***masked***';
        ApiRequestLog::create($this->pdo, [
            'request_method' => $request->method(), 'request_path' => $request->path(),
            'product_slug' => (string) ($payload['product_slug'] ?? $payload['product_id'] ?? ''),
            'domain' => (string) ($payload['domain'] ?? $payload['site_url'] ?? ''),
            'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'response_status' => $response->status,
            'error_code' => (string) ($context['error_code'] ?? ''),
            'request_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_json' => ($response->headers['Content-Type'] ?? '') === 'application/json; charset=UTF-8' ? $response->body : null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
