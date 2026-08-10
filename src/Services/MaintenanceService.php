<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class MaintenanceService
{
    public function __construct(private readonly PDO $pdo, private readonly array $securityConfig) {}
    public function runAll(): array
    {
        return ['expired_licenses_marked' => $this->markExpiredLicenses(), 'expired_tokens_deleted' => $this->cleanupExpiredTokens(), 'old_api_logs_deleted' => $this->cleanupLogs('api_request_logs', (int) $this->securityConfig['log_retention_days']), 'old_security_events_deleted' => $this->cleanupLogs('security_events', (int) $this->securityConfig['log_retention_days']), 'old_download_logs_deleted' => $this->cleanupLogs('download_logs', (int) $this->securityConfig['log_retention_days']), 'old_audit_logs_deleted' => $this->cleanupLogs('audit_logs', (int) $this->securityConfig['audit_retention_days'])];
    }
    public function markExpiredLicenses(): int
    {
        $s = $this->pdo->prepare('UPDATE licenses SET status = "expired", updated_at = :updated_at WHERE is_lifetime = 0 AND expires_at IS NOT NULL AND expires_at < :now AND status = "active"');
        $s->execute(['updated_at' => date('Y-m-d H:i:s'), 'now' => date('Y-m-d H:i:s')]); return $s->rowCount();
    }
    public function cleanupExpiredTokens(): int { $s = $this->pdo->prepare('DELETE FROM download_tokens WHERE expires_at < :now OR used_at IS NOT NULL'); $s->execute(['now' => date('Y-m-d H:i:s')]); return $s->rowCount(); }
    private function cleanupLogs(string $table, int $days): int { $s = $this->pdo->prepare('DELETE FROM ' . $table . ' WHERE created_at < :threshold'); $s->execute(['threshold' => date('Y-m-d H:i:s', strtotime('-' . $days . ' days'))]); return $s->rowCount(); }
}
