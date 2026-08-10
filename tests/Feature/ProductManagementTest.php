<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\Admin\ProductController;
use App\Http\Request;
use Tests\TestCase;

final class ProductManagementTest extends TestCase
{
    public function test_version_can_be_updated_from_admin_panel(): void
    {
        $product = $this->createProduct();
        $version = $this->addVersion((int) $product['id'], '1.1.0');
        $controller = new ProductController($this->app);

        $response = $controller->updateVersion($this->makePostRequest('/admin/products/' . $product['id'] . '/versions/' . $version['id'], [
            'version' => '1.2.0',
            'published_at' => '2026-08-10 12:00:00',
            'channel' => 'stable',
            'release_status' => 'published',
            'min_wordpress_version' => '6.1',
            'min_php_version' => '8.2',
            'changelog' => 'Updated release',
        ]), ['id' => (string) $product['id'], 'versionId' => (string) $version['id']]);

        self::assertSame(302, $response->status);
        self::assertSame('/admin/products/' . $product['id'], $response->headers['Location'] ?? null);

        $updatedVersion = $this->app->productService()->getVersionById((int) $product['id'], (int) $version['id']);
        $updatedProduct = $this->app->productService()->getById((int) $product['id']);
        self::assertSame('1.2.0', $updatedVersion['version']);
        self::assertSame('Updated release', $updatedVersion['changelog']);
        self::assertSame('1.2.0', $updatedProduct['current_version']);
    }

    public function test_deleting_current_version_refreshes_product_current_version(): void
    {
        $product = $this->createProduct();
        $older = $this->addVersion((int) $product['id'], '1.0.0');
        $newer = $this->addVersion((int) $product['id'], '1.2.0');
        $controller = new ProductController($this->app);

        $response = $controller->deleteVersion($this->makePostRequest('/admin/products/' . $product['id'] . '/versions/' . $newer['id'] . '/delete'), ['id' => (string) $product['id'], 'versionId' => (string) $newer['id']]);

        self::assertSame(302, $response->status);
        $deletedAt = $this->app->db()->query('SELECT deleted_at FROM product_versions WHERE id = ' . (int) $newer['id'])->fetchColumn();
        $updatedProduct = $this->app->productService()->getById((int) $product['id']);
        self::assertNotNull($deletedAt);
        self::assertSame('1.0.0', $updatedProduct['current_version']);
        self::assertNotNull($this->app->productService()->getVersionById((int) $product['id'], (int) $older['id']));
    }

    public function test_product_with_licenses_cannot_be_deleted(): void
    {
        $product = $this->createProduct();
        $this->createLicense((int) $product['id']);
        $controller = new ProductController($this->app);

        $response = $controller->destroy($this->makePostRequest('/admin/products/' . $product['id'] . '/delete'), ['id' => (string) $product['id']]);

        self::assertSame(302, $response->status);
        self::assertSame('/admin/products/' . $product['id'], $response->headers['Location'] ?? null);
        self::assertNull($this->app->db()->query('SELECT deleted_at FROM products WHERE id = ' . (int) $product['id'])->fetchColumn());
    }

    public function test_product_without_licenses_can_be_soft_deleted_with_versions(): void
    {
        $product = $this->createProduct();
        $version = $this->addVersion((int) $product['id'], '1.1.0');
        $controller = new ProductController($this->app);

        $response = $controller->destroy($this->makePostRequest('/admin/products/' . $product['id'] . '/delete'), ['id' => (string) $product['id']]);

        self::assertSame(302, $response->status);
        self::assertSame('/admin/products', $response->headers['Location'] ?? null);
        self::assertNotNull($this->app->db()->query('SELECT deleted_at FROM products WHERE id = ' . (int) $product['id'])->fetchColumn());
        self::assertNotNull($this->app->db()->query('SELECT deleted_at FROM product_versions WHERE id = ' . (int) $version['id'])->fetchColumn());
    }

    private function makePostRequest(string $uri, array $post = []): Request
    {
        $session = [];
        return new Request(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => $uri, 'REMOTE_ADDR' => '127.0.0.1'], [], $post, [], [], $session, '');
    }
}
