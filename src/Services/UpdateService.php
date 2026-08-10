<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class UpdateService
{
    public function __construct(private readonly \PDO $pdo, private readonly ProductService $productService, private readonly LicenseService $licenseService, private readonly DownloadTokenService $downloadTokenService, private readonly DomainService $domainService, private readonly array $appConfig) {}
    public function check(string $productSlug, string $currentVersion, string $licenseKey, string $domain, string $channel, string $ip): array
    {
        [$product, $license] = $this->licenseService->validateForProduct($productSlug, $licenseKey);
        if (!$this->licenseService->updatesAllowed($license)) throw new RuntimeException('updates_not_allowed');
        $canonicalDomain = $this->domainService->canonicalize($domain);
        if (!$this->licenseService->findActivation((int) $license['id'], $canonicalDomain) && !$this->domainService->isDevelopmentDomain($canonicalDomain)) throw new RuntimeException('domain_not_allowed');
        $latest = $this->productService->latestVersionForChannel((int) $product['id'], $channel);
        if (!$latest || version_compare($latest['version'], $currentVersion, '<=')) return ['update_available' => false, 'product' => $product, 'latest' => $latest];
        $token = $this->downloadTokenService->create((int) $product['id'], (int) $latest['id'], (int) $license['id'], $canonicalDomain, $ip);
        return ['update_available' => true, 'product' => $product, 'latest' => $latest, 'download_token' => $token, 'download_url' => rtrim((string) $this->appConfig['url'], '/') . '/api/v1/updates/download?token=' . urlencode($token['plain'])];
    }
    public function latestProductMeta(string $productSlug, string $channel = 'stable'): ?array
    {
        $product = $this->productService->getBySlug($productSlug); if (!$product || !(bool) $product['is_active']) return null;
        $latest = $this->productService->latestVersionForChannel((int) $product['id'], $channel); if (!$latest) return null;
        return ['product' => $product, 'latest' => $latest];
    }
}
