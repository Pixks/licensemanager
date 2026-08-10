<?php

declare(strict_types=1);

use App\Database\Migration;
use PDO;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS products (id ' . $this->idColumn($pdo) . ', name ' . $this->stringType(190) . ' NOT NULL, slug ' . $this->stringType(120) . ' NOT NULL, description ' . $this->textType() . ' NULL, is_active ' . $this->boolType() . ' NOT NULL DEFAULT 1, current_version ' . $this->stringType(50) . ' NULL, default_channel ' . $this->stringType(20) . ' NOT NULL DEFAULT "stable", icon_path ' . $this->stringType(255) . ' NULL, ' . $this->createdUpdated($pdo) . ', ' . $this->softDeletes() . ', UNIQUE(slug))');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS product_versions (id ' . $this->idColumn($pdo) . ', product_id ' . $this->foreignId($pdo) . ', version ' . $this->stringType(50) . ' NOT NULL, published_at ' . $this->dateTimeType() . ' NULL, changelog ' . $this->textType() . ' NULL, min_wordpress_version ' . $this->stringType(30) . ' NULL, min_php_version ' . $this->stringType(30) . ' NULL, channel ' . $this->stringType(20) . ' NOT NULL DEFAULT "stable", zip_path ' . $this->stringType(255) . ' NOT NULL, sha256_hash ' . $this->stringType(64) . ' NOT NULL, release_status ' . $this->stringType(20) . ' NOT NULL DEFAULT "draft", ' . $this->createdUpdated($pdo) . ', ' . $this->softDeletes() . ', FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, UNIQUE(product_id, version, channel))');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_product_versions_product_status ON product_versions(product_id, release_status, channel)');
    }
};
