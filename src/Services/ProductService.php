<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVersion;
use PDO;

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
        return Product::create($this->pdo, ['name' => $data['name'], 'slug' => $data['slug'], 'description' => $data['description'] ?? '', 'is_active' => !empty($data['is_active']) ? 1 : 0, 'current_version' => $data['current_version'] ?? null, 'icon_path' => $data['icon_path'] ?? null, 'default_channel' => $data['default_channel'] ?? 'stable', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted_at' => null])->attributes;
    }
    public function updateProduct(int $id, array $data): void
    {
        Product::updateById($this->pdo, $id, ['name' => $data['name'], 'slug' => $data['slug'], 'description' => $data['description'] ?? '', 'is_active' => !empty($data['is_active']) ? 1 : 0, 'current_version' => $data['current_version'] ?? null, 'default_channel' => $data['default_channel'] ?? 'stable', 'updated_at' => date('Y-m-d H:i:s')]);
    }
    public function addVersion(int $productId, array $data): array
    {
        $version = ProductVersion::create($this->pdo, ['product_id' => $productId, 'version' => $data['version'], 'published_at' => $data['published_at'] ?? date('Y-m-d H:i:s'), 'changelog' => $data['changelog'] ?? '', 'min_wordpress_version' => $data['min_wordpress_version'] ?? null, 'min_php_version' => $data['min_php_version'] ?? null, 'channel' => $data['channel'] ?? 'stable', 'zip_path' => $data['zip_path'], 'sha256_hash' => $data['sha256_hash'], 'release_status' => $data['release_status'] ?? 'draft', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted_at' => null])->attributes;
        if (($data['release_status'] ?? 'draft') === 'published') $this->syncCurrentVersion($productId, $data['version']);
        return $version;
    }
    public function versionsForProduct(int $productId): array { $s = $this->pdo->prepare('SELECT * FROM product_versions WHERE product_id = :product_id AND deleted_at IS NULL ORDER BY published_at DESC, id DESC'); $s->execute(['product_id' => $productId]); return $s->fetchAll() ?: []; }
    public function latestVersionForChannel(int $productId, string $channel = 'stable'): ?array
    {
        $s = $this->pdo->prepare('SELECT * FROM product_versions WHERE product_id = :product_id AND release_status = "published" AND deleted_at IS NULL AND (channel = :channel OR (:channel = "beta" AND channel = "stable")) ORDER BY published_at DESC, id DESC');
        $s->execute(['product_id' => $productId, 'channel' => $channel]); $versions = $s->fetchAll() ?: [];
        if ($versions === []) return null; usort($versions, static fn (array $a, array $b): int => version_compare($b['version'], $a['version'])); return $versions[0] ?? null;
    }
    public function syncCurrentVersion(int $productId, string $version): void { Product::updateById($this->pdo, $productId, ['current_version' => $version, 'updated_at' => date('Y-m-d H:i:s')]); }
}
