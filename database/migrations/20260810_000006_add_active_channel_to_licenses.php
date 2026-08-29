<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        // active_channel records the channel the license has committed to.
        // NULL  = not yet locked (user has not switched to beta)
        // 'beta' = permanently locked to beta; switching back to stable is blocked
        // Only 'beta' causes a lock; 'stable' is never stored here.
        try {
            $this->exec($pdo, 'ALTER TABLE licenses ADD COLUMN active_channel VARCHAR(20) NULL DEFAULT NULL');
        } catch (\Throwable) {
            // Column already exists — safe to ignore
        }
    }
};
