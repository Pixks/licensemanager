<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use PDO;

final class AuditLogService
{
    public function __construct(private readonly PDO $pdo) {}
    public function log(?int $userId, string $action, string $targetType, ?int $targetId, array $before = [], array $after = [], string $ip = ''): void
    {
        AuditLog::create($this->pdo, [
            'user_id' => $userId, 'action' => $action, 'target_type' => $targetType, 'target_id' => $targetId,
            'before_json' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'after_json' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip_address' => $ip, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
