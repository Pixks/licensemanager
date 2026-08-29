<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        // Add allowed_channels to licenses (defaults to 'stable,beta' — all existing licenses get both channels)
        try {
            $this->exec($pdo, 'ALTER TABLE licenses ADD COLUMN allowed_channels VARCHAR(40) NOT NULL DEFAULT "stable,beta"');
        } catch (\Throwable) {
            // Column already exists — safe to ignore
        }

        // Add plans (JSON) to products (defaults to NULL — no plan restrictions)
        try {
            $this->exec($pdo, 'ALTER TABLE products ADD COLUMN plans TEXT NULL');
        } catch (\Throwable) {
            // Column already exists — safe to ignore
        }
    }
};
