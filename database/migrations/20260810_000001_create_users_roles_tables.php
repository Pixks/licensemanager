<?php

declare(strict_types=1);

use App\Database\Migration;
return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS roles (id ' . $this->idColumn($pdo) . ', name ' . $this->stringType(100) . ' NOT NULL, slug ' . $this->stringType(100) . ' NOT NULL, description ' . $this->textType() . ' NULL, ' . $this->createdUpdated($pdo) . ', ' . $this->softDeletes() . ', UNIQUE(slug))');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS permissions (id ' . $this->idColumn($pdo) . ', name ' . $this->stringType(120) . ' NOT NULL, slug ' . $this->stringType(120) . ' NOT NULL, description ' . $this->textType() . ' NULL, ' . $this->createdUpdated($pdo) . ', ' . $this->softDeletes() . ', UNIQUE(slug))');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS users (id ' . $this->idColumn($pdo) . ', name ' . $this->stringType(150) . ' NOT NULL, email ' . $this->stringType(190) . ' NOT NULL, password_hash ' . $this->stringType(255) . ' NOT NULL, is_active ' . $this->boolType() . ' NOT NULL DEFAULT 1, last_login_at ' . $this->dateTimeType() . ' NULL, ' . $this->createdUpdated($pdo) . ', ' . $this->softDeletes() . ', UNIQUE(email))');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS role_user (role_id ' . $this->foreignId($pdo) . ', user_id ' . $this->foreignId($pdo) . ', PRIMARY KEY (role_id, user_id), FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS permission_role (permission_id ' . $this->foreignId($pdo) . ', role_id ' . $this->foreignId($pdo) . ', PRIMARY KEY (permission_id, role_id), FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE, FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE)');
        $this->exec($pdo, 'CREATE TABLE IF NOT EXISTS password_reset_tokens (email ' . $this->stringType(190) . ' PRIMARY KEY, token_hash ' . $this->stringType(255) . ' NOT NULL, created_at ' . $this->dateTimeType() . ' NOT NULL)');
    }
};
