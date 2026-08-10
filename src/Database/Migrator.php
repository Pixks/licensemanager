<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class Migrator
{
    public function __construct(private readonly PDO $pdo, private readonly string $path) {}
    public function migrate(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS migrations (migration VARCHAR(255) PRIMARY KEY, ran_at DATETIME NOT NULL)');
        $ran = $this->pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach (glob($this->path . '/*.php') ?: [] as $file) {
            $name = basename($file);
            if (in_array($name, $ran, true)) {
                continue;
            }
            $migration = require $file;
            $migration->up($this->pdo);
            $statement = $this->pdo->prepare('INSERT INTO migrations (migration, ran_at) VALUES (:migration, :ran_at)');
            $statement->execute(['migration' => $name, 'ran_at' => date('Y-m-d H:i:s')]);
        }
    }
}
