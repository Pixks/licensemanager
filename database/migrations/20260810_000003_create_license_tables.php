<?php

declare(strict_types=1);

use App\Database\Migration;
use PDO;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS licenses (id ' . $this->idColumn($pdo) . ', product_id ' . $this->foreignId($pdo) . ', plan_name ' . $this->stringType(120) . ' NOT NULL, status ' . $this->stringType(20) . ' NOT NULL DEFAULT "active", activation_limit INTEGER NOT NULL DEFAULT 1, activations_in_use INTEGER NOT NULL DEFAULT 0, first_activated_at ' . $this->dateTimeType() . ' NULL, expires_at ' . $this->dateTimeType() . ' NULL, updates_expires_at ' . $this->dateTimeType() . ' NULL, support_expires_at ' . $this->dateTimeType() . ' NULL, is_lifetime ' . $this->boolType() . ' NOT NULL DEFAULT 0, customer_email ' . $this->stringType(190) . ' NULL, admin_note ' . $this->textType() . ' NULL, updates_allowed ' . $this->boolType() . ' NOT NULL DEFAULT 1, support_active ' . $this->boolType() . ' NOT NULL DEFAULT 1, key_hash ' . $this->stringType(64) . ' NOT NULL, key_prefix ' . $this->stringType(16) . ' NOT NULL, key_suffix ' . $this->stringType(8) . ' NOT NULL, masked_key ' . $this->stringType(64) . ' NOT NULL, ' . $this->createdUpdated($pdo) . ', ' . $this->softDeletes() . ', FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, UNIQUE(key_hash))');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_licenses_product_status ON licenses(product_id, status)');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_licenses_prefix_suffix ON licenses(key_prefix, key_suffix)');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS license_activations (id ' . $this->idColumn($pdo) . ', license_id ' . $this->foreignId($pdo) . ', product_id ' . $this->foreignId($pdo) . ', domain ' . $this->stringType(190) . ' NOT NULL, canonical_domain ' . $this->stringType(190) . ' NOT NULL, site_url ' . $this->stringType(255) . ' NOT NULL, fingerprint ' . $this->stringType(255) . ' NULL, ip_address ' . $this->stringType(64) . ' NULL, user_agent ' . $this->stringType(255) . ' NULL, activated_at ' . $this->dateTimeType() . ' NOT NULL, last_checked_at ' . $this->dateTimeType() . ' NULL, activation_status ' . $this->stringType(20) . ' NOT NULL DEFAULT "active", deactivated_at ' . $this->dateTimeType() . ' NULL, deactivation_reason ' . $this->textType() . ' NULL, ' . $this->createdUpdated($pdo) . ', ' . $this->softDeletes() . ', FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE)');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_activations_license_domain ON license_activations(license_id, canonical_domain, activation_status)');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_activations_product_domain ON license_activations(product_id, canonical_domain)');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS license_domain_rules (id ' . $this->idColumn($pdo) . ', license_id ' . $this->foreignId($pdo) . ', rule_type ' . $this->stringType(20) . ' NOT NULL DEFAULT "allow", pattern ' . $this->stringType(190) . ' NOT NULL, notes ' . $this->textType() . ' NULL, ' . $this->createdUpdated($pdo) . ', ' . $this->softDeletes() . ', FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE)');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_domain_rules_license ON license_domain_rules(license_id, rule_type)');
    }
};
