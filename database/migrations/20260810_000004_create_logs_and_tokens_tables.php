<?php

declare(strict_types=1);

use App\Database\Migration;
use PDO;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS download_tokens (id ' . $this->idColumn($pdo) . ', product_id ' . $this->foreignId($pdo) . ', product_version_id ' . $this->foreignId($pdo) . ', license_id ' . $this->foreignId($pdo) . ', canonical_domain ' . $this->stringType(190) . ' NOT NULL, token_hash ' . $this->stringType(64) . ' NOT NULL, expires_at ' . $this->dateTimeType() . ' NOT NULL, used_at ' . $this->dateTimeType() . ' NULL, issued_ip ' . $this->stringType(64) . ' NULL, ' . $this->createdUpdated($pdo) . ', ' . $this->softDeletes() . ', FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, FOREIGN KEY (product_version_id) REFERENCES product_versions(id) ON DELETE CASCADE, FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE, UNIQUE(token_hash))');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_download_tokens_expires ON download_tokens(expires_at, used_at)');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS download_logs (id ' . $this->idColumn($pdo) . ', download_token_id ' . $this->foreignId($pdo, true) . ', license_id ' . $this->foreignId($pdo) . ', product_id ' . $this->foreignId($pdo) . ', product_version_id ' . $this->foreignId($pdo) . ', ip_address ' . $this->stringType(64) . ' NULL, user_agent ' . $this->stringType(255) . ' NULL, file_path ' . $this->stringType(255) . ' NOT NULL, ' . $this->createdUpdated($pdo) . ', FOREIGN KEY (download_token_id) REFERENCES download_tokens(id) ON DELETE SET NULL, FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, FOREIGN KEY (product_version_id) REFERENCES product_versions(id) ON DELETE CASCADE)');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_download_logs_license ON download_logs(license_id, created_at)');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS api_request_logs (id ' . $this->idColumn($pdo) . ', request_method ' . $this->stringType(12) . ' NOT NULL, request_path ' . $this->stringType(190) . ' NOT NULL, product_slug ' . $this->stringType(120) . ' NULL, domain ' . $this->stringType(190) . ' NULL, ip_address ' . $this->stringType(64) . ' NULL, user_agent ' . $this->stringType(255) . ' NULL, response_status INTEGER NOT NULL, error_code ' . $this->stringType(120) . ' NULL, request_json ' . $this->textType() . ' NULL, response_json ' . $this->textType() . ' NULL, ' . $this->createdUpdated($pdo) . ')');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_api_logs_path_status ON api_request_logs(request_path, response_status, created_at)');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_api_logs_product_domain ON api_request_logs(product_slug, domain)');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS audit_logs (id ' . $this->idColumn($pdo) . ', user_id ' . $this->foreignId($pdo, true) . ', action ' . $this->stringType(190) . ' NOT NULL, target_type ' . $this->stringType(120) . ' NOT NULL, target_id INTEGER NULL, before_json ' . $this->textType() . ' NULL, after_json ' . $this->textType() . ' NULL, ip_address ' . $this->stringType(64) . ' NULL, ' . $this->createdUpdated($pdo) . ', FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL)');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_audit_logs_target ON audit_logs(target_type, target_id, created_at)');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS security_events (id ' . $this->idColumn($pdo) . ', event_type ' . $this->stringType(120) . ' NOT NULL, ip_address ' . $this->stringType(64) . ' NULL, user_agent ' . $this->stringType(255) . ' NULL, context_json ' . $this->textType() . ' NULL, ' . $this->createdUpdated($pdo) . ')');
        $this->exec($pdo, 'CREATE INDEX IF NOT EXISTS idx_security_events_type ON security_events(event_type, created_at)');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS settings (id ' . $this->idColumn($pdo) . ', setting_key ' . $this->stringType(190) . ' NOT NULL, setting_value ' . $this->textType() . ' NULL, setting_type ' . $this->stringType(50) . ' NOT NULL DEFAULT "string", ' . $this->createdUpdated($pdo) . ', ' . $this->softDeletes() . ', UNIQUE(setting_key))');
    }
};
