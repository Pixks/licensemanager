<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use RuntimeException;

final class LicenseApiController extends Controller
{
    public function activate(Request $request): Response
    {
        return $this->respond($request, function () use ($request): array {
            $licenseKey = (string) ($request->input('license_key') ?: $request->header('X-License-Key', ''));
            $result = $this->app->licenseService()->activate((string) $request->input('product_slug'), $licenseKey, (string) $request->input('domain', ''), (string) $request->input('site_url', ''), $request->input('fingerprint') ?: null, $request->ip(), $request->userAgent());
            return ['success' => true, 'data' => ['status' => 'active', 'license_status' => $result['status'], 'expires_at' => $result['license']['expires_at'], 'updates_expires_at' => $result['license']['updates_expires_at'], 'support_expires_at' => $result['license']['support_expires_at'], 'activations_in_use' => $result['license']['activations_in_use'], 'activation_limit' => $result['license']['activation_limit'], 'canonical_domain' => $result['activation']['canonical_domain'] ?? $this->app->domainService()->canonicalize((string) $request->input('domain', '')), 'reused' => (bool) ($result['activation']['reused'] ?? false), 'grace_period_days' => (int) $this->app->config('app.grace_period_days', 10)]];
        });
    }
    public function deactivate(Request $request): Response
    {
        return $this->respond($request, function () use ($request): array {
            $licenseKey = (string) ($request->input('license_key') ?: $request->header('X-License-Key', ''));
            $result = $this->app->licenseService()->deactivate((string) $request->input('product_slug'), $licenseKey, (string) $request->input('domain', ''), (string) $request->input('reason', 'client_request'));
            return ['success' => true, 'data' => ['status' => 'deactivated', 'deactivated' => (bool) $result['deactivated'], 'canonical_domain' => $result['canonical_domain'], 'activations_in_use' => $result['license']['activations_in_use'], 'activation_limit' => $result['license']['activation_limit']]];
        });
    }
    public function check(Request $request): Response
    {
        return $this->respond($request, function () use ($request): array {
            $licenseKey = (string) ($request->input('license_key') ?: $request->header('X-License-Key', ''));
            $result = $this->app->licenseService()->check((string) $request->input('product_slug'), $licenseKey, (string) $request->input('domain', ''));
            return ['success' => true, 'data' => ['status' => $result['status'], 'is_active_for_domain' => (bool) $result['activation'], 'canonical_domain' => $result['canonical_domain'], 'expires_at' => $result['license']['expires_at'], 'updates_expires_at' => $result['license']['updates_expires_at'], 'support_expires_at' => $result['license']['support_expires_at'], 'updates_allowed' => $this->app->licenseService()->updatesAllowed($result['license']), 'activation_limit' => $result['license']['activation_limit'], 'activations_in_use' => $this->app->licenseService()->activeActivationsCount((int) $result['license']['id']), 'grace_period_days' => $result['grace_period_days']]];
        });
    }
    public function heartbeat(Request $request): Response
    {
        return $this->respond($request, function () use ($request): array {
            $licenseKey = (string) ($request->input('license_key') ?: $request->header('X-License-Key', ''));
            $result = $this->app->licenseService()->heartbeat((string) $request->input('product_slug'), $licenseKey, (string) $request->input('domain', ''));
            return ['success' => true, 'data' => ['status' => $result['status'], 'is_active_for_domain' => (bool) $result['activation'], 'grace_period_days' => $result['grace_period_days'], 'checked_at' => date('c')]];
        });
    }
    private function respond(Request $request, callable $callback): Response
    {
        try { $response = $this->json($callback()); $this->app->apiLogService()->log($request, $response); return $response; }
        catch (RuntimeException $e) {
            $code = $e->getMessage();
            $status = match ($code) { 'license_not_found', 'invalid_license_key', 'invalid_request' => 422, 'product_mismatch', 'domain_not_allowed', 'activation_limit_reached', 'license_expired', 'license_revoked', 'license_suspended' => 403, default => 400 };
            $response = $this->json(['success' => false, 'error' => ['code' => $code, 'message' => $this->humanMessage($code)]], $status); $this->app->apiLogService()->log($request, $response, ['error_code' => $code]); return $response;
        }
    }
    private function humanMessage(string $code): string
    {
        return match ($code) { 'invalid_license_key' => 'The provided license key is invalid.', 'license_not_found' => 'The license could not be found.', 'license_expired' => 'The license has expired.', 'license_revoked' => 'The license has been revoked.', 'license_suspended' => 'The license has been suspended.', 'activation_limit_reached' => 'The activation limit has been reached.', 'domain_not_allowed' => 'This domain is not allowed for the license.', 'product_mismatch' => 'The license does not belong to the requested product.', default => 'The request could not be processed.' };
    }
}
