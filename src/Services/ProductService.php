<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVersion;
use PDO;
use RuntimeException;

final class ProductService
{
    public function __construct(private readonly PDO $pdo) {}
    public function listProducts(): array
    {
        return $this->pdo->query('SELECT p.*, (SELECT COUNT(*) FROM product_versions pv WHERE pv.product_id = p.id AND pv.deleted_at IS NULL) AS versions_count FROM products p WHERE p.deleted_at IS NULL ORDER BY p.id DESC')->fetchAll() ?: [];
    }
    public function getBySlug(string $slug): ?array { $s = $this->pdo->prepare('SELECT * FROM products WHERE slug = :slug AND deleted_at IS NULL LIMIT 1'); $s->execute(['slug' => $slug]); return $s->fetch() ?: null; }
    public function getById(int $id): ?array { return Product::find($this->pdo, $id)?->attributes; }
    public function createProduct(array $data): array
    {
        return Product::create($this->pdo, ['name' => $data['name'], 'slug' => $data['slug'], 'description' => $data['description'] ?? '', 'is_active' => !empty($data['is_active']) ? 1 : 0, 'current_version' => $data['current_version'] ?? null, 'icon_path' => $data['icon_path'] ?? null, 'default_channel' => $data['default_channel'] ?? 'stable', 'plans' => $this->normalizePlans($data['plans_input'] ?? null), 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted_at' => null])->attributes;
    }
    public function updateProduct(int $id, array $data): void
    {
        Product::updateById($this->pdo, $id, ['name' => $data['name'], 'slug' => $data['slug'], 'description' => $data['description'] ?? '', 'is_active' => !empty($data['is_active']) ? 1 : 0, 'current_version' => $data['current_version'] ?? null, 'default_channel' => $data['default_channel'] ?? 'stable', 'plans' => $this->normalizePlans($data['plans_input'] ?? null), 'updated_at' => date('Y-m-d H:i:s')]);
    }
    /** Convert comma-separated plans string to JSON or null */
    public function normalizePlans(?string $input): ?string
    {
        if ($input === null || trim($input) === '') return null;
        $plans = array_values(array_filter(array_map('trim', explode(',', $input))));
        return $plans !== [] ? json_encode($plans) : null;
    }
    /** Decode JSON plans column to array. Returns [] if not set. */
    public function getPlans(array $product): array
    {
        if (empty($product['plans'])) return [];
        $decoded = json_decode((string) $product['plans'], true);
        return is_array($decoded) ? $decoded : [];
    }
    public function addVersion(int $productId, array $data): array
    {
        $version = ProductVersion::create($this->pdo, ['product_id' => $productId, 'version' => $data['version'], 'published_at' => $data['published_at'] ?? date('Y-m-d H:i:s'), 'changelog' => $data['changelog'] ?? '', 'min_wordpress_version' => $data['min_wordpress_version'] ?? null, 'min_php_version' => $data['min_php_version'] ?? null, 'channel' => $data['channel'] ?? 'stable', 'zip_path' => $data['zip_path'], 'sha256_hash' => $data['sha256_hash'], 'release_status' => $data['release_status'] ?? 'draft', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted_at' => null])->attributes;
        if (($data['release_status'] ?? 'draft') === 'published') $this->syncCurrentVersion($productId, $data['version']);
        return $version;
    }
    public function versionsForProduct(int $productId): array { $s = $this->pdo->prepare('SELECT * FROM product_versions WHERE product_id = :product_id AND deleted_at IS NULL ORDER BY published_at DESC, id DESC'); $s->execute(['product_id' => $productId]); return $s->fetchAll() ?: []; }
    public function getVersionById(int $productId, int $versionId): ?array
    {
        $s = $this->pdo->prepare('SELECT * FROM product_versions WHERE id = :id AND product_id = :product_id AND deleted_at IS NULL LIMIT 1');
        $s->execute(['id' => $versionId, 'product_id' => $productId]);
        return $s->fetch() ?: null;
    }
    public function updateVersion(int $productId, int $versionId, array $data): ?array
    {
        $current = $this->getVersionById($productId, $versionId);
        if (!$current) return null;
        $payload = [
            'version' => $data['version'],
            'published_at' => $data['published_at'] ?? null,
            'changelog' => $data['changelog'] ?? '',
            'min_wordpress_version' => $data['min_wordpress_version'] ?? null,
            'min_php_version' => $data['min_php_version'] ?? null,
            'channel' => $data['channel'] ?? 'stable',
            'release_status' => $data['release_status'] ?? 'draft',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (isset($data['zip_path'], $data['sha256_hash'])) {
            $payload['zip_path'] = $data['zip_path'];
            $payload['sha256_hash'] = $data['sha256_hash'];
        }
        ProductVersion::updateById($this->pdo, $versionId, $payload);
        if (($payload['release_status'] ?? 'draft') === 'published') {
            $this->syncCurrentVersion($productId, (string) $payload['version']);
        } else {
            $this->refreshCurrentVersion($productId);
        }
        return $this->getVersionById($productId, $versionId);
    }
    public function deleteVersion(int $productId, int $versionId): ?array
    {
        $version = $this->getVersionById($productId, $versionId);
        if (!$version) return null;
        ProductVersion::updateById($this->pdo, $versionId, ['deleted_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->refreshCurrentVersion($productId);
        return $version;
    }
    public function latestVersionForChannel(int $productId, string $channel = 'stable'): ?array
    {
        // Beta channel falls back to stable versions too; use two separate params to avoid PDO named-param reuse issues on SQLite
        $s = $this->pdo->prepare('SELECT * FROM product_versions WHERE product_id = :product_id AND release_status = "published" AND deleted_at IS NULL AND (channel = :ch1 OR (:ch2 = "beta" AND channel = "stable"))');
        $s->execute(['product_id' => $productId, 'ch1' => $channel, 'ch2' => $channel]); $versions = $s->fetchAll() ?: [];
        if ($versions === []) return null; usort($versions, static fn (array $a, array $b): int => version_compare($b['version'], $a['version'])); return $versions[0] ?? null;
    }
    public function syncCurrentVersion(int $productId, string $version): void { Product::updateById($this->pdo, $productId, ['current_version' => $version, 'updated_at' => date('Y-m-d H:i:s')]); }
    public function deleteProduct(int $productId): ?array
    {
        $product = $this->getById($productId);
        if (!$product) return null;
        if ($this->hasProductDependencies($productId)) throw new RuntimeException('product_has_licenses');
        $timestamp = date('Y-m-d H:i:s');
        $versions = $this->versionsForProduct($productId);
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare('UPDATE product_versions SET deleted_at = :deleted_at, updated_at = :updated_at WHERE product_id = :product_id AND deleted_at IS NULL');
            $statement->execute(['deleted_at' => $timestamp, 'updated_at' => $timestamp, 'product_id' => $productId]);
            Product::updateById($this->pdo, $productId, ['deleted_at' => $timestamp, 'updated_at' => $timestamp]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return ['product' => $product, 'versions' => $versions];
    }
    public function hasProductDependencies(int $productId): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM licenses WHERE product_id = :product_id AND deleted_at IS NULL');
        $statement->execute(['product_id' => $productId]);
        return (int) $statement->fetchColumn() > 0;
    }
    public function refreshCurrentVersion(int $productId): void
    {
        $product = $this->getById($productId);
        if (!$product) return;
        $versions = $this->versionsForProduct($productId);
        $published = array_values(array_filter($versions, static fn (array $version): bool => ($version['release_status'] ?? '') === 'published'));
        if ($published === []) {
            Product::updateById($this->pdo, $productId, ['current_version' => null, 'updated_at' => date('Y-m-d H:i:s')]);
            return;
        }
        usort($published, static fn (array $a, array $b): int => version_compare((string) $b['version'], (string) $a['version']));
        $preferredChannel = (string) ($product['default_channel'] ?? 'stable');
        $preferred = array_values(array_filter($published, static fn (array $version): bool => ($version['channel'] ?? 'stable') === $preferredChannel));
        $selected = $preferred[0] ?? $published[0];
        Product::updateById($this->pdo, $productId, ['current_version' => $selected['version'], 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
