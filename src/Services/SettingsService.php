<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use PDO;

final class SettingsService
{
    public function __construct(private readonly PDO $pdo) {}
    public function all(): array
    {
        $settings = [];
        foreach (Setting::all($this->pdo, 'deleted_at IS NULL', []) as $setting) $settings[$setting->attributes['setting_key']] = $setting->attributes['setting_value'];
        return $settings;
    }
    public function set(string $key, string $value, string $type = 'string'): void
    {
        $statement = $this->pdo->prepare('SELECT id FROM settings WHERE setting_key = :setting_key LIMIT 1');
        $statement->execute(['setting_key' => $key]);
        $existing = $statement->fetchColumn();
        $payload = ['setting_key' => $key, 'setting_value' => $value, 'setting_type' => $type, 'updated_at' => date('Y-m-d H:i:s')];
        if ($existing) { Setting::updateById($this->pdo, (int) $existing, $payload); return; }
        $payload['created_at'] = date('Y-m-d H:i:s'); $payload['deleted_at'] = null; Setting::create($this->pdo, $payload);
    }
}
