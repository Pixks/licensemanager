<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

abstract class Migration
{
    abstract public function up(PDO $pdo): void;
    public function down(PDO $pdo): void {}
    protected function driver(PDO $pdo): string { return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME); }
    protected function idColumn(PDO $pdo): string { return $this->driver($pdo) === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY'; }
    protected function foreignId(PDO $pdo, bool $nullable = false): string { $type = $this->driver($pdo) === 'sqlite' ? 'INTEGER' : 'BIGINT UNSIGNED'; return $type . ($nullable ? ' NULL' : ' NOT NULL'); }
    protected function stringType(int $length = 255): string { return 'VARCHAR(' . $length . ')'; }
    protected function textType(): string { return 'TEXT'; }
    protected function boolType(): string { return 'TINYINT(1)'; }
    protected function dateTimeType(): string { return 'DATETIME'; }
    protected function createdUpdated(PDO $pdo): string
    {
        return $this->driver($pdo) === 'sqlite'
            ? 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'
            : 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
    }
    protected function softDeletes(): string { return 'deleted_at DATETIME NULL'; }
    protected function exec(PDO $pdo, string $sql): void { $pdo->exec($sql); }
}
