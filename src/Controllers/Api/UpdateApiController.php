<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Models\ProductVersion;
use RuntimeException;

final class UpdateApiController extends Controller
{
    public function check(Request $request): Response
    {
        try {
            $licenseKey = (string) ($request->input('license_key') ?: $request->header('X-License-Key', ''));
            $result = $this->app->updateService()->check((string) $request->input('product_slug'), (string) $request->input('current_version'), $licenseKey, (string) $request->input('domain'), (string) $request->input('channel', 'stable'), $request->ip());
            $response = $this->json(['success' => true, 'data' => ['update_available' => $result['update_available'], 'latest_version' => $result['latest']['version'] ?? null, 'channel' => $result['channel'] ?? $result['latest']['channel'] ?? null, 'changelog' => $result['latest']['changelog'] ?? null, 'min_wordpress_version' => $result['latest']['min_wordpress_version'] ?? null, 'min_php_version' => $result['latest']['min_php_version'] ?? null, 'sha256' => $result['latest']['sha256_hash'] ?? null, 'download_url' => $result['download_url'] ?? null, 'published_at' => $result['latest']['published_at'] ?? null]]);
            $this->app->apiLogService()->log($request, $response); return $response;
        } catch (RuntimeException $e) {
            $response = $this->json(['success' => false, 'error' => ['code' => $e->getMessage(), 'message' => 'Update check failed.']], 403);
            $this->app->apiLogService()->log($request, $response, ['error_code' => $e->getMessage()]); return $response;
        }
    }
    public function download(Request $request): Response
    {
        try {
            $token = $this->app->downloadTokenService()->validate((string) $request->query('token')); $version = ProductVersion::find($this->app->db(), (int) $token['product_version_id']);
            if (!$version || !is_file((string) $version->attributes['zip_path'])) throw new RuntimeException('invalid_download_token');
            $this->app->downloadTokenService()->markUsed((int) $token['id']); $this->app->downloadTokenService()->logDownload($token, (string) $version->attributes['zip_path'], $request->ip(), $request->userAgent());
            return Response::download((string) $version->attributes['zip_path'], basename((string) $version->attributes['zip_path']));
        } catch (RuntimeException $e) {
            return $this->json(['success' => false, 'error' => ['code' => 'invalid_download_token', 'message' => 'The download token is invalid or expired.']], 403);
        }
    }
    public function latest(Request $request, array $params): Response
    {
        $meta = $this->app->updateService()->latestProductMeta((string) $params['product_slug'], (string) $request->query('channel', 'stable'));
        if (!$meta) return $this->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Product not found.']], 404);
        return $this->json(['success' => true, 'data' => ['product' => ['name' => $meta['product']['name'], 'slug' => $meta['product']['slug'], 'description' => $meta['product']['description']], 'latest' => ['version' => $meta['latest']['version'], 'channel' => $meta['latest']['channel'], 'changelog' => $meta['latest']['changelog'], 'min_wordpress_version' => $meta['latest']['min_wordpress_version'], 'min_php_version' => $meta['latest']['min_php_version'], 'published_at' => $meta['latest']['published_at'], 'sha256' => $meta['latest']['sha256_hash']]]]);
    }
}
