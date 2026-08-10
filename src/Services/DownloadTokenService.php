<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DownloadLog;
use App\Models\DownloadToken;
use PDO;
use RuntimeException;

final class DownloadTokenService
{
    public function __construct(private readonly PDO $pdo, private readonly string $appKey, private readonly int $ttlMinutes, private readonly bool $singleUse) {}
    public function create(int $productId, int $productVersionId, int $licenseId, string $canonicalDomain, string $ip): array
    {
        $plain = bin2hex(random_bytes(24)); $expiresAt = date('Y-m-d H:i:s', time() + ($this->ttlMinutes * 60));
        $record = DownloadToken::create($this->pdo, ['product_id' => $productId, 'product_version_id' => $productVersionId, 'license_id' => $licenseId, 'canonical_domain' => $canonicalDomain, 'token_hash' => hash_hmac('sha256', $plain, $this->appKey), 'expires_at' => $expiresAt, 'used_at' => null, 'issued_ip' => $ip, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted_at' => null])->attributes;
        return ['plain' => $plain, 'record' => $record];
    }
    public function validate(string $plainToken): array
    {
        $s = $this->pdo->prepare('SELECT * FROM download_tokens WHERE token_hash = :token_hash AND deleted_at IS NULL LIMIT 1');
        $s->execute(['token_hash' => hash_hmac('sha256', $plainToken, $this->appKey)]); $token = $s->fetch();
        if (!$token || (!empty($token['expires_at']) && strtotime((string) $token['expires_at']) < time()) || ($this->singleUse && !empty($token['used_at']))) throw new RuntimeException('invalid_download_token');
        return $token;
    }
    public function markUsed(int $tokenId): void { DownloadToken::updateById($this->pdo, $tokenId, ['used_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]); }
    public function logDownload(array $token, string $filePath, string $ip, string $userAgent): void
    {
        DownloadLog::create($this->pdo, ['download_token_id' => (int) $token['id'], 'license_id' => (int) $token['license_id'], 'product_id' => (int) $token['product_id'], 'product_version_id' => (int) $token['product_version_id'], 'ip_address' => $ip, 'user_agent' => $userAgent, 'file_path' => $filePath, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
