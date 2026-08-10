<?php

declare(strict_types=1);

namespace Tests;

use App\Application;
use Database\Seeders\RoleSeeder;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;
    protected function setUp(): void
    {
        parent::setUp(); @unlink(base_path('database/testing.sqlite')); touch(base_path('database/testing.sqlite'));
        $this->app = new Application(base_path()); $this->app->migrator()->migrate(); (new RoleSeeder($this->app))->run();
    }
    protected function createProduct(string $slug = 'my-plugin-pro'): array { return $this->app->productService()->createProduct(['name' => 'My Plugin Pro', 'slug' => $slug, 'description' => 'Example premium plugin', 'is_active' => 1, 'current_version' => '1.0.0', 'default_channel' => 'stable']); }
    protected function addVersion(int $productId, string $version = '1.1.0', string $channel = 'stable', string $status = 'published'): array
    {
        $package = storage_path('app/private/packages/' . uniqid('pkg_', true) . '.zip'); if (!is_dir(dirname($package))) mkdir(dirname($package), 0775, true); file_put_contents($package, 'zip-content-' . $version);
        return $this->app->productService()->addVersion($productId, ['version' => $version, 'published_at' => date('Y-m-d H:i:s'), 'changelog' => 'Changes for ' . $version, 'min_wordpress_version' => '6.0', 'min_php_version' => '8.2', 'channel' => $channel, 'zip_path' => $package, 'sha256_hash' => hash_file('sha256', $package), 'release_status' => $status]);
    }
    protected function createLicense(int $productId, array $overrides = []): array
    {
        $licenses = $this->app->licenseService()->generateLicenses(array_merge(['product_id' => $productId, 'plan_name' => 'pro', 'status' => 'active', 'activation_limit' => 1, 'updates_allowed' => true, 'support_active' => true, 'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')), 'updates_expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')), 'support_expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))], $overrides), 1);
        return $licenses[0];
    }
}
