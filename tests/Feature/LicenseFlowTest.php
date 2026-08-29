<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\Admin\LicenseController;
use App\Http\Request;
use Tests\TestCase;

final class LicenseFlowTest extends TestCase
{
    public function test_successful_activation(): void
    {
        $product = $this->createProduct(); $license = $this->createLicense((int) $product['id']); $result = $this->app->licenseService()->activate($product['slug'], $license['plain_key'], 'example.com', 'https://example.com', null, '127.0.0.1', 'PHPUnit');
        self::assertSame('active', $result['status']); self::assertSame(1, $result['license']['activations_in_use']);
    }
    public function test_activation_limit_is_enforced(): void
    {
        $product = $this->createProduct(); $license = $this->createLicense((int) $product['id'], ['activation_limit' => 1]); $this->app->licenseService()->activate($product['slug'], $license['plain_key'], 'example.com', 'https://example.com', null, '127.0.0.1', 'PHPUnit');
        $this->expectExceptionMessage('activation_limit_reached'); $this->app->licenseService()->activate($product['slug'], $license['plain_key'], 'second.example.com', 'https://second.example.com', null, '127.0.0.1', 'PHPUnit');
    }
    public function test_activation_on_disallowed_domain_fails(): void
    {
        $product = $this->createProduct(); $license = $this->createLicense((int) $product['id']);
        \App\Models\LicenseDomainRule::create($this->app->db(), ['license_id' => $license['record']['id'], 'rule_type' => 'allow', 'pattern' => 'allowed.example.com', 'notes' => null, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted_at' => null]);
        $this->expectExceptionMessage('domain_not_allowed'); $this->app->licenseService()->activate($product['slug'], $license['plain_key'], 'blocked.example.com', 'https://blocked.example.com', null, '127.0.0.1', 'PHPUnit');
    }
    public function test_reactivating_same_domain_reuses_slot(): void
    {
        $product = $this->createProduct(); $license = $this->createLicense((int) $product['id']); $this->app->licenseService()->activate($product['slug'], $license['plain_key'], 'example.com', 'https://example.com', null, '127.0.0.1', 'PHPUnit'); $result = $this->app->licenseService()->activate($product['slug'], $license['plain_key'], 'www.example.com', 'https://www.example.com', null, '127.0.0.1', 'PHPUnit');
        self::assertTrue($result['activation']['reused']); self::assertSame(1, $this->app->licenseService()->activeActivationsCount((int) $license['record']['id']));
    }
    public function test_deactivation_frees_slot(): void
    {
        $product = $this->createProduct(); $license = $this->createLicense((int) $product['id']); $this->app->licenseService()->activate($product['slug'], $license['plain_key'], 'example.com', 'https://example.com', null, '127.0.0.1', 'PHPUnit'); $result = $this->app->licenseService()->deactivate($product['slug'], $license['plain_key'], 'example.com');
        self::assertTrue($result['deactivated']); self::assertSame(0, $result['license']['activations_in_use']);
    }
    public function test_expired_license_is_rejected(): void
    {
        $product = $this->createProduct(); $license = $this->createLicense((int) $product['id'], ['expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]); $this->expectExceptionMessage('license_expired'); $this->app->licenseService()->check($product['slug'], $license['plain_key'], 'example.com');
    }
    public function test_revoked_license_is_rejected(): void
    {
        $product = $this->createProduct(); $license = $this->createLicense((int) $product['id'], ['status' => 'revoked']); $this->expectExceptionMessage('license_revoked'); $this->app->licenseService()->check($product['slug'], $license['plain_key'], 'example.com');
    }
    public function test_product_mismatch_is_rejected(): void
    {
        $productA = $this->createProduct('plugin-a'); $productB = $this->createProduct('plugin-b'); $license = $this->createLicense((int) $productA['id']); $this->expectExceptionMessage('product_mismatch'); $this->app->licenseService()->check($productB['slug'], $license['plain_key'], 'example.com');
    }
    public function test_update_check_for_valid_license_returns_download_url(): void
    {
        $product = $this->createProduct(); $this->addVersion((int) $product['id'], '1.1.0'); $license = $this->createLicense((int) $product['id']); $this->app->licenseService()->activate($product['slug'], $license['plain_key'], 'example.com', 'https://example.com', null, '127.0.0.1', 'PHPUnit'); $result = $this->app->updateService()->check($product['slug'], '1.0.0', $license['plain_key'], 'example.com', 'stable', '127.0.0.1');
        self::assertTrue($result['update_available']); self::assertNotEmpty($result['download_url']);
    }
    public function test_update_check_for_invalid_license_fails(): void
    {
        $product = $this->createProduct(); $this->addVersion((int) $product['id'], '1.1.0'); $license = $this->createLicense((int) $product['id'], ['updates_allowed' => false]); $this->app->licenseService()->activate($product['slug'], $license['plain_key'], 'example.com', 'https://example.com', null, '127.0.0.1', 'PHPUnit'); $this->expectExceptionMessage('updates_not_allowed'); $this->app->updateService()->check($product['slug'], '1.0.0', $license['plain_key'], 'example.com', 'stable', '127.0.0.1');
    }
    public function test_beta_channel_cannot_fall_back_to_stable_without_admin_reset(): void
    {
        $product = $this->createProduct(); $this->addVersion((int) $product['id'], '1.1.0'); $this->addVersion((int) $product['id'], '1.2.0-beta', 'beta'); $license = $this->createLicense((int) $product['id']); $this->app->licenseService()->activate($product['slug'], $license['plain_key'], 'example.com', 'https://example.com', null, '127.0.0.1', 'PHPUnit');
        $betaResult = $this->app->updateService()->check($product['slug'], '1.0.0', $license['plain_key'], 'example.com', 'beta', '127.0.0.1');
        self::assertSame('beta', $betaResult['channel']);
        self::assertSame('beta', $this->app->licenseService()->findLicenseByPlainKey($license['plain_key'])['active_channel'] ?? null);
        $stableResult = $this->app->updateService()->check($product['slug'], '1.0.0', $license['plain_key'], 'example.com', 'stable', '127.0.0.1');
        self::assertSame('beta', $stableResult['channel']);
    }
    public function test_admin_can_reset_beta_channel_lock(): void
    {
        $product = $this->createProduct(); $license = $this->createLicense((int) $product['id']);
        $this->app->licenseService()->commitChannel($license['record'], 'beta');
        $controller = new LicenseController($this->app);
        $response = $controller->resetChannel($this->makePostRequest('/admin/licenses/' . $license['record']['id'] . '/reset-channel'), ['id' => (string) $license['record']['id']]);
        self::assertSame(302, $response->status);
        self::assertSame('/admin/licenses/' . $license['record']['id'], $response->headers['Location'] ?? null);
        self::assertNull($this->app->licenseService()->findLicenseByPlainKey($license['plain_key'])['active_channel'] ?? null);
    }
    public function test_reset_channel_for_missing_license_returns_error_redirect(): void
    {
        $controller = new LicenseController($this->app);
        $response = $controller->resetChannel($this->makePostRequest('/admin/licenses/999/reset-channel'), ['id' => '999']);
        self::assertSame(302, $response->status);
        self::assertSame('/admin/licenses', $response->headers['Location'] ?? null);
        self::assertSame('Nie znaleziono licencji.', $_SESSION['_flash']['error'] ?? null);
    }
    public function test_download_token_can_be_generated_and_validates(): void
    {
        $product = $this->createProduct(); $version = $this->addVersion((int) $product['id'], '1.1.0'); $license = $this->createLicense((int) $product['id']); $token = $this->app->downloadTokenService()->create((int) $product['id'], (int) $version['id'], (int) $license['record']['id'], 'example.com', '127.0.0.1'); $validated = $this->app->downloadTokenService()->validate($token['plain']); self::assertSame($token['record']['id'], $validated['id']);
    }
    public function test_zip_download_requires_valid_token(): void
    {
        $product = $this->createProduct(); $version = $this->addVersion((int) $product['id'], '1.1.0'); $license = $this->createLicense((int) $product['id']); $token = $this->app->downloadTokenService()->create((int) $product['id'], (int) $version['id'], (int) $license['record']['id'], 'example.com', '127.0.0.1'); $controller = new \App\Controllers\Api\UpdateApiController($this->app);
        $session = []; $response = $controller->download(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/updates/download?token=' . $token['plain']], ['token' => $token['plain']], [], [], [], $session, '')); self::assertSame(200, $response->status); self::assertArrayHasKey('Content-Disposition', $response->headers);
        $session2 = []; $invalidResponse = $controller->download(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/updates/download?token=invalid'], ['token' => 'invalid'], [], [], [], $session2, '')); self::assertSame(403, $invalidResponse->status);
    }
    private function makePostRequest(string $uri, array $post = []): Request
    {
        $session = [];
        return new Request(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => $uri, 'REMOTE_ADDR' => '127.0.0.1'], [], $post, [], [], $session, '');
    }
}
