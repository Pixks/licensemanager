<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\License;
use App\Models\LicenseActivation;
use PDO;
use RuntimeException;

final class LicenseService
{
    public function __construct(private readonly PDO $pdo, private readonly LicenseKeyService $licenseKeyService, private readonly DomainService $domainService, private readonly ProductService $productService, private readonly AuditLogService $auditLogService, private readonly array $appConfig) {}
    public function generateLicenses(array $data, int $quantity = 1): array
    {
        $data = $this->sanitizeLicenseData($data);
        $created = [];
        for ($i = 0; $i < $quantity; $i++) {
            $plain = $this->licenseKeyService->generate();
            $created[] = ['plain_key' => $plain, 'record' => License::create($this->pdo, [
                'product_id' => (int) $data['product_id'], 'plan_name' => $data['plan_name'] ?? 'default', 'status' => $data['status'] ?? 'active',
                'activation_limit' => (int) ($data['activation_limit'] ?? 1), 'activations_in_use' => 0, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                'first_activated_at' => null, 'expires_at' => $data['expires_at'] ?? null, 'updates_expires_at' => $data['updates_expires_at'] ?? null, 'support_expires_at' => $data['support_expires_at'] ?? null,
                'is_lifetime' => !empty($data['is_lifetime']) ? 1 : 0, 'customer_email' => $data['customer_email'] ?? null, 'admin_note' => $data['admin_note'] ?? null,
                'updates_allowed' => !empty($data['updates_allowed']) ? 1 : 0, 'support_active' => !empty($data['support_active']) ? 1 : 0,
                'allowed_channels' => $data['allowed_channels'] ?? 'stable,beta',
                'key_hash' => $this->licenseKeyService->hash($plain), 'key_prefix' => $this->licenseKeyService->prefix($plain), 'key_suffix' => $this->licenseKeyService->suffix($plain), 'masked_key' => $this->licenseKeyService->mask($plain), 'deleted_at' => null,
            ])->attributes];
        }
        return $created;
    }

    /**
     * Sanitize license data: clear date fields that should not apply based on flags.
     * - is_lifetime: clears expires_at, updates_expires_at, support_expires_at
     * - updates_allowed=false: clears updates_expires_at
     * - support_active=false: clears support_expires_at
     */
    public function sanitizeLicenseData(array $data): array
    {
        if (!empty($data['is_lifetime'])) {
            $data['expires_at'] = null;
            $data['updates_expires_at'] = null;
            $data['support_expires_at'] = null;
        }
        if (empty($data['updates_allowed'])) {
            $data['updates_expires_at'] = null;
        }
        if (empty($data['support_active'])) {
            $data['support_expires_at'] = null;
        }
        return $data;
    }
    public function findLicenseByPlainKey(string $licenseKey): ?array { $s = $this->pdo->prepare('SELECT * FROM licenses WHERE key_hash = :key_hash AND deleted_at IS NULL LIMIT 1'); $s->execute(['key_hash' => $this->licenseKeyService->hash($licenseKey)]); return $s->fetch() ?: null; }
    public function searchLicenses(array $filters = []): array
    {
        $where = ['l.deleted_at IS NULL']; $params = [];
        if (!empty($filters['product_id'])) { $where[] = 'l.product_id = :product_id'; $params['product_id'] = $filters['product_id']; }
        if (!empty($filters['status'])) { $where[] = 'l.status = :status'; $params['status'] = $filters['status']; }
        if (!empty($filters['customer_email'])) { $where[] = 'l.customer_email LIKE :customer_email'; $params['customer_email'] = '%' . $filters['customer_email'] . '%'; }
        if (!empty($filters['key_fragment'])) { $fragment = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $filters['key_fragment']) ?? ''); $where[] = '(l.key_prefix LIKE :fragment OR l.key_suffix LIKE :fragment OR l.masked_key LIKE :masked_fragment)'; $params['fragment'] = '%' . $fragment . '%'; $params['masked_fragment'] = '%' . $filters['key_fragment'] . '%'; }
        if (!empty($filters['domain'])) { $where[] = 'EXISTS (SELECT 1 FROM license_activations la WHERE la.license_id = l.id AND la.canonical_domain LIKE :domain AND la.deleted_at IS NULL)'; $params['domain'] = '%' . $this->domainService->canonicalize((string) $filters['domain']) . '%'; }
        $limit = max(1, (int) ($filters['limit'] ?? 500));
        $offset = max(0, (int) ($filters['offset'] ?? 0));
        $sql = 'SELECT l.*, p.name AS product_name FROM licenses l INNER JOIN products p ON p.id = l.product_id WHERE ' . implode(' AND ', $where) . ' ORDER BY l.id DESC LIMIT :limit OFFSET :offset';
        $params['limit'] = $limit; $params['offset'] = $offset;
        $s = $this->pdo->prepare($sql); $s->execute($params); return $s->fetchAll() ?: [];
    }
    public function statusForLicense(array $license): string
    {
        if ((int) $license['is_lifetime'] === 1) return $license['status'];
        if (!empty($license['expires_at']) && strtotime((string) $license['expires_at']) < time() && !in_array($license['status'], ['revoked', 'suspended'], true)) return 'expired';
        return $license['status'];
    }
    public function updatesAllowed(array $license): bool
    {
        if (!(bool) $license['updates_allowed']) return false;
        $status = $this->statusForLicense($license);
        if (in_array($status, ['revoked', 'suspended'], true)) return false;
        // Lifetime licenses ignore date-based expiry for updates
        if ((int) $license['is_lifetime'] === 1) return true;
        if (!empty($license['updates_expires_at']) && strtotime((string) $license['updates_expires_at']) < time()) return false;
        return $status === 'active';
    }

    public function supportActive(array $license): bool
    {
        if (!(bool) $license['support_active']) return false;
        $status = $this->statusForLicense($license);
        if (in_array($status, ['revoked', 'suspended'], true)) return false;
        // Lifetime licenses ignore date-based expiry for support
        if ((int) $license['is_lifetime'] === 1) return true;
        if (!empty($license['support_expires_at']) && strtotime((string) $license['support_expires_at']) < time()) return false;
        return $status === 'active';
    }

    public function isChannelAllowed(array $license, string $channel): bool
    {
        $allowed = array_map('trim', explode(',', (string) ($license['allowed_channels'] ?? 'stable,beta')));
        return in_array($channel, $allowed, true);
    }

    /**
     * Resolve the effective channel: use requested if allowed, otherwise fall back to 'stable'.
     */
    public function resolveChannel(array $license, string $requestedChannel): string
    {
        return $this->isChannelAllowed($license, $requestedChannel) ? $requestedChannel : 'stable';
    }
    public function validateForProduct(string $productSlug, string $licenseKey): array
    {
        $product = $this->productService->getBySlug($productSlug);
        if (!$product || !(bool) $product['is_active']) throw new RuntimeException('product_mismatch');
        $license = $this->findLicenseByPlainKey($licenseKey);
        if (!$license) throw new RuntimeException('license_not_found');
        if ((int) $license['product_id'] !== (int) $product['id']) throw new RuntimeException('product_mismatch');
        $status = $this->statusForLicense($license);
        if ($status === 'expired') throw new RuntimeException('license_expired');
        if ($status === 'revoked') throw new RuntimeException('license_revoked');
        if ($status === 'suspended') throw new RuntimeException('license_suspended');
        if ($status !== 'active') throw new RuntimeException('invalid_license_key');
        return [$product, $license];
    }
    public function domainAllowed(int $licenseId, string $canonicalDomain): bool
    {
        if ($this->domainService->isDevelopmentDomain($canonicalDomain)) return true;
        $s = $this->pdo->prepare('SELECT * FROM license_domain_rules WHERE license_id = :license_id AND deleted_at IS NULL ORDER BY id ASC');
        $s->execute(['license_id' => $licenseId]); $rules = $s->fetchAll() ?: [];
        if ($rules === []) return true;
        $allowed = false;
        foreach ($rules as $rule) {
            if ($this->domainService->matchesRule($canonicalDomain, (string) $rule['pattern'])) {
                if ($rule['rule_type'] === 'deny') return false;
                $allowed = true;
            }
        }
        return $allowed;
    }
    public function activeActivationsCount(int $licenseId): int { $s = $this->pdo->prepare('SELECT COUNT(*) FROM license_activations WHERE license_id = :license_id AND activation_status = "active" AND deleted_at IS NULL'); $s->execute(['license_id' => $licenseId]); return (int) $s->fetchColumn(); }
    public function findActivation(int $licenseId, string $canonicalDomain): ?array
    {
        $s = $this->pdo->prepare('SELECT * FROM license_activations WHERE license_id = :license_id AND canonical_domain = :canonical_domain AND activation_status = "active" AND deleted_at IS NULL LIMIT 1');
        $s->execute(['license_id' => $licenseId, 'canonical_domain' => $canonicalDomain]); return $s->fetch() ?: null;
    }
    public function activate(string $productSlug, string $licenseKey, string $domain, string $siteUrl, ?string $fingerprint, string $ip, string $userAgent): array
    {
        [$product, $license] = $this->validateForProduct($productSlug, $licenseKey);
        $canonicalDomain = $this->domainService->canonicalize($domain ?: $siteUrl);
        if (!$this->domainService->validate($canonicalDomain) || !$this->domainAllowed((int) $license['id'], $canonicalDomain)) throw new RuntimeException('domain_not_allowed');
        $existing = $this->findActivation((int) $license['id'], $canonicalDomain);
        if ($existing) {
            LicenseActivation::updateById($this->pdo, (int) $existing['id'], ['site_url' => $this->domainService->normalizeSiteUrl($siteUrl), 'fingerprint' => $fingerprint, 'ip_address' => $ip, 'user_agent' => $userAgent, 'last_checked_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            return ['product' => $product, 'license' => $license, 'activation' => array_merge($existing, ['reused' => true]), 'status' => $this->statusForLicense($license)];
        }
        $limit = (int) $license['activation_limit'];
        if ($limit > 0 && $this->activeActivationsCount((int) $license['id']) >= $limit) throw new RuntimeException('activation_limit_reached');
        $activation = LicenseActivation::create($this->pdo, ['license_id' => (int) $license['id'], 'product_id' => (int) $product['id'], 'domain' => $domain, 'canonical_domain' => $canonicalDomain, 'site_url' => $this->domainService->normalizeSiteUrl($siteUrl), 'fingerprint' => $fingerprint, 'ip_address' => $ip, 'user_agent' => $userAgent, 'activated_at' => date('Y-m-d H:i:s'), 'last_checked_at' => date('Y-m-d H:i:s'), 'activation_status' => 'active', 'deactivated_at' => null, 'deactivation_reason' => null, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted_at' => null])->attributes;
        $activationsInUse = $this->activeActivationsCount((int) $license['id']);
        License::updateById($this->pdo, (int) $license['id'], ['activations_in_use' => $activationsInUse, 'first_activated_at' => $license['first_activated_at'] ?: date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        return ['product' => $product, 'license' => array_merge($license, ['activations_in_use' => $activationsInUse]), 'activation' => array_merge($activation, ['reused' => false]), 'status' => $this->statusForLicense($license)];
    }
    public function deactivate(string $productSlug, string $licenseKey, string $domain, string $reason = 'client_request'): array
    {
        [$product, $license] = $this->validateForProduct($productSlug, $licenseKey);
        $canonicalDomain = $this->domainService->canonicalize($domain); $activation = $this->findActivation((int) $license['id'], $canonicalDomain);
        if ($activation) LicenseActivation::updateById($this->pdo, (int) $activation['id'], ['activation_status' => 'deactivated', 'deactivated_at' => date('Y-m-d H:i:s'), 'deactivation_reason' => $reason, 'updated_at' => date('Y-m-d H:i:s')]);
        $count = $this->activeActivationsCount((int) $license['id']); License::updateById($this->pdo, (int) $license['id'], ['activations_in_use' => $count, 'updated_at' => date('Y-m-d H:i:s')]);
        return ['product' => $product, 'license' => array_merge($license, ['activations_in_use' => $count]), 'deactivated' => (bool) $activation, 'canonical_domain' => $canonicalDomain];
    }
    public function check(string $productSlug, string $licenseKey, string $domain): array
    {
        [$product, $license] = $this->validateForProduct($productSlug, $licenseKey);
        $canonicalDomain = $this->domainService->canonicalize($domain); $activation = $this->findActivation((int) $license['id'], $canonicalDomain);
        return ['product' => $product, 'license' => $license, 'activation' => $activation, 'status' => $this->statusForLicense($license), 'canonical_domain' => $canonicalDomain, 'grace_period_days' => (int) $this->appConfig['grace_period_days']];
    }
    public function heartbeat(string $productSlug, string $licenseKey, string $domain): array
    {
        $result = $this->check($productSlug, $licenseKey, $domain);
        if ($result['activation']) LicenseActivation::updateById($this->pdo, (int) $result['activation']['id'], ['last_checked_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        return $result;
    }
    public function releaseActivation(int $activationId, string $reason = 'admin_release'): void
    {
        $activation = LicenseActivation::find($this->pdo, $activationId); if (!$activation) return;
        LicenseActivation::updateById($this->pdo, $activationId, ['activation_status' => 'released', 'deactivated_at' => date('Y-m-d H:i:s'), 'deactivation_reason' => $reason, 'updated_at' => date('Y-m-d H:i:s')]);
        $licenseId = (int) $activation->attributes['license_id'];
        License::updateById($this->pdo, $licenseId, ['activations_in_use' => $this->activeActivationsCount($licenseId), 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
