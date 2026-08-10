<?php

declare(strict_types=1);

namespace App\Support;

use App\Application;
use ZipArchive;

final class DeploymentHelper
{
    public static function install(string $basePath): int
    {
        $envPath = $basePath . '/.env';
        $examplePath = $basePath . '/.env.example';
        if (!is_file($envPath)) {
            if (!is_file($examplePath)) {
                fwrite(STDERR, "Missing .env.example file.\n");
                return 1;
            }
            copy($examplePath, $envPath);
            echo "Created .env from .env.example.\n";
        }
        $current = self::readEnvFile($envPath);
        $appUrl = self::askRequired('APP_URL', $current['APP_URL'] ?? 'https://licenses.example.com');
        while (!filter_var($appUrl, FILTER_VALIDATE_URL)) {
            echo "APP_URL must be a valid URL.\n";
            $appUrl = self::askRequired('APP_URL', $current['APP_URL'] ?? 'https://licenses.example.com');
        }
        $updates = [
            'APP_URL' => $appUrl,
            'DB_HOST' => self::askRequired('DB_HOST', $current['DB_HOST'] ?? '127.0.0.1'),
            'DB_PORT' => self::askRequired('DB_PORT', $current['DB_PORT'] ?? '3306'),
            'DB_DATABASE' => self::askRequired('DB_DATABASE', $current['DB_DATABASE'] ?? 'licensemanager'),
            'DB_USERNAME' => self::askRequired('DB_USERNAME', $current['DB_USERNAME'] ?? 'licensemanager'),
            'DB_PASSWORD' => self::askRequired('DB_PASSWORD', $current['DB_PASSWORD'] ?? ''),
            'INITIAL_ADMIN_EMAIL' => self::askRequired('INITIAL_ADMIN_EMAIL', $current['INITIAL_ADMIN_EMAIL'] ?? 'admin@example.com'),
            'INITIAL_ADMIN_PASSWORD' => self::askRequired('INITIAL_ADMIN_PASSWORD', $current['INITIAL_ADMIN_PASSWORD'] ?? ''),
            'INITIAL_ADMIN_NAME' => self::askRequired('INITIAL_ADMIN_NAME', $current['INITIAL_ADMIN_NAME'] ?? 'Super Admin'),
            'APP_FORCE_HTTPS' => 'true',
        ];
        $appKey = (string) ($current['APP_KEY'] ?? '');
        $updates['APP_KEY'] = self::isPlaceholder($appKey, ['change-me', 'development-key']) || $appKey === '' ? bin2hex(random_bytes(32)) : $appKey;
        $cronSecret = (string) ($current['CRON_SECRET'] ?? '');
        $updates['CRON_SECRET'] = self::isPlaceholder($cronSecret, ['change-me']) || $cronSecret === '' ? bin2hex(random_bytes(24)) : $cronSecret;
        self::writeEnvFile($envPath, $updates);
        echo "Updated .env.\n";
        Env::load($envPath);
        $app = new Application($basePath);
        try {
            $app->migrator()->migrate();
            echo "Migrations completed.\n";
            (new \Database\Seeders\RoleSeeder($app))->run();
            echo "Roles and permissions seeded.\n";
            $app->userService()->createOrUpdateAdmin($updates['INITIAL_ADMIN_NAME'], $updates['INITIAL_ADMIN_EMAIL'], $updates['INITIAL_ADMIN_PASSWORD'], 'superadmin');
            echo "Administrator ready: {$updates['INITIAL_ADMIN_EMAIL']}\n";
        } catch (\Throwable $e) {
            fwrite(STDERR, "Install failed: {$e->getMessage()}\n");
            return 1;
        }
        echo "Install finished.\n";
        return 0;
    }

    public static function check(string $basePath): int
    {
        $envPath = $basePath . '/.env';
        if (!is_file($envPath)) {
            fwrite(STDERR, "[ERROR] Missing .env file.\n");
            return 1;
        }
        Env::load($envPath);
        $errors = [];
        $warnings = [];
        $appUrl = (string) env('APP_URL', '');
        $forceHttps = filter_var(env('APP_FORCE_HTTPS', true), FILTER_VALIDATE_BOOL);
        if ($appUrl === '' || !filter_var($appUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'APP_URL is missing or invalid.';
        } elseif ($forceHttps && parse_url($appUrl, PHP_URL_SCHEME) !== 'https') {
            $errors[] = 'APP_URL must use https when APP_FORCE_HTTPS=true.';
        }
        if (self::isPlaceholder((string) env('CRON_SECRET', ''), ['change-me']) || (string) env('CRON_SECRET', '') === '') {
            $errors[] = 'CRON_SECRET is missing or still placeholder.';
        }
        foreach (['storage/cache', 'storage/logs', 'storage/app/private'] as $relative) {
            $path = $basePath . '/' . $relative;
            if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
                $errors[] = "Cannot create {$relative}.";
                continue;
            }
            if (!is_writable($path)) {
                $errors[] = "{$relative} is not writable.";
            }
        }
        try {
            $app = new Application($basePath);
            $app->db();
        } catch (\Throwable $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }
        if (!is_file($basePath . '/public/.htaccess')) {
            $warnings[] = 'public/.htaccess is missing.';
        }
        echo empty($errors) ? "[OK] Setup validation passed.\n" : "[ERROR] Setup validation failed.\n";
        foreach ($errors as $error) echo " - {$error}\n";
        foreach ($warnings as $warning) echo " - [WARN] {$warning}\n";
        return empty($errors) ? 0 : 1;
    }

    public static function smoke(string $basePath, ?string $url = null): int
    {
        Env::load($basePath . '/.env');
        $baseUrl = rtrim($url ?: (string) env('APP_URL', ''), '/');
        if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            fwrite(STDERR, "Provide a valid URL or set APP_URL.\n");
            return 1;
        }
        $checks = [
            ['path' => '/login', 'allowed' => [200, 302]],
            ['path' => '/api/v1/products/nonexistent/latest', 'allowed' => [200, 400, 401, 403, 404, 422]],
        ];
        $failed = false;
        foreach ($checks as $check) {
            $result = self::request($baseUrl . $check['path']);
            $status = $result['status'];
            $ok = in_array($status, $check['allowed'], true);
            $suffix = $result['error'] !== null ? " ({$result['error']})" : '';
            echo ($ok ? '[OK]' : '[ERROR]') . " {$check['path']} -> {$status}{$suffix}\n";
            if (!$ok) $failed = true;
        }
        return $failed ? 1 : 0;
    }

    public static function buildZip(string $basePath, ?string $outputPath = null): int
    {
        if (!class_exists(ZipArchive::class)) {
            fwrite(STDERR, "ZipArchive extension is required.\n");
            return 1;
        }
        $target = $outputPath ?: $basePath . '/storage/releases/licensemanager-' . date('Ymd-His') . '.zip';
        $targetDir = dirname($target);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            fwrite(STDERR, "Cannot create release directory.\n");
            return 1;
        }
        $zip = new ZipArchive();
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            fwrite(STDERR, "Cannot open zip file for writing.\n");
            return 1;
        }
        $excludedPrefixes = ['.git/', 'storage/cache/', 'storage/logs/', 'storage/releases/', 'tests/', '.github/'];
        $excludedFiles = ['.env', 'database/testing.sqlite'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $absolute = $file->getPathname();
            $relative = str_replace('\\', '/', ltrim(substr($absolute, strlen($basePath)), '/'));
            if (in_array($relative, $excludedFiles, true)) continue;
            $skip = false;
            foreach ($excludedPrefixes as $prefix) {
                if (str_starts_with($relative, $prefix)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;
            $zip->addFile($absolute, $relative);
        }
        $zip->close();
        echo "Created release package: {$target}\n";
        return 0;
    }

    private static function askRequired(string $name, string $default = ''): string
    {
        while (true) {
            $label = $default !== '' ? "{$name} [{$default}]: " : "{$name}: ";
            if (function_exists('readline')) {
                $value = (string) readline($label);
            } else {
                echo $label;
                $line = fgets(STDIN);
                $value = $line === false ? '' : trim($line);
            }
            $value = trim($value);
            if ($value === '' && $default !== '') return $default;
            if ($value !== '') return $value;
            echo "{$name} is required.\n";
        }
    }

    private static function readEnvFile(string $path): array
    {
        $data = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $data[trim($key)] = trim(trim($value), "\"'");
        }
        return $data;
    }

    private static function writeEnvFile(string $path, array $updates): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        $written = [];
        foreach ($lines as &$line) {
            if (!str_contains($line, '=')) continue;
            [$key] = explode('=', $line, 2);
            $key = trim($key);
            if (!array_key_exists($key, $updates)) continue;
            $line = $key . '=' . self::encodeEnvValue((string) $updates[$key]);
            $written[$key] = true;
        }
        unset($line);
        foreach ($updates as $key => $value) {
            if (isset($written[$key])) continue;
            $lines[] = $key . '=' . self::encodeEnvValue((string) $value);
        }
        file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    private static function isPlaceholder(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (stripos($value, $pattern) !== false) return true;
        }
        return false;
    }

    private static function encodeEnvValue(string $value): string
    {
        if ($value !== '' && preg_match('/^[A-Za-z0-9._:@\/-]+$/', $value) === 1) return $value;
        $escaped = str_replace(['\\', '"'], ['\\\\', '\"'], $value);
        return '"' . $escaped . '"';
    }

    private static function request(string $url): array
    {
        $context = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 15, 'ignore_errors' => true], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $warning = null;
        set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
            $warning = $message;
            return true;
        });
        file_get_contents($url, false, $context);
        restore_error_handler();
        $headers = $http_response_header ?? [];
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', $header, $match) === 1) return ['status' => (int) $match[1], 'error' => $warning];
        }
        return ['status' => 0, 'error' => $warning];
    }
}
