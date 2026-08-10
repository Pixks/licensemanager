<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SecurityEvent;
use PDO;

final class SecurityService
{
    public function __construct(private readonly PDO $pdo) {}
    public function log(string $type, array $context = []): void
    {
        SecurityEvent::create($this->pdo, [
            'event_type' => $type,
            'ip_address' => (string) ($context['ip'] ?? ''),
            'user_agent' => (string) ($context['user_agent'] ?? ''),
            'context_json' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
