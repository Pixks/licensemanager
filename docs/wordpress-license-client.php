<?php
class Pixks_WP_License_Client
{
    private string $apiBase; private string $productSlug; private string $version; private string $optionKey; private string $cacheKey;
    public function __construct(string $apiBase, string $productSlug, string $version) { $this->apiBase = rtrim($apiBase, '/'); $this->productSlug = $productSlug; $this->version = $version; $this->optionKey = $productSlug . '_license_key'; $this->cacheKey = $productSlug . '_license_status_cache'; }
    public function getLicenseKey(): string { return (string) get_option($this->optionKey, ''); }
    public function saveLicenseKey(string $licenseKey): void { update_option($this->optionKey, sanitize_text_field($licenseKey), false); }
    public function getCanonicalDomain(): string { $host = wp_parse_url(home_url(), PHP_URL_HOST) ?: ''; return trim((string) preg_replace('/^www\./', '', strtolower($host)), '.'); }
    public function activate(): array { return $this->post('/api/v1/licenses/activate', ['product_slug' => $this->productSlug, 'license_key' => $this->getLicenseKey(), 'domain' => $this->getCanonicalDomain(), 'site_url' => home_url(), 'fingerprint' => md5(home_url() . ABSPATH)]); }
    public function deactivate(): array { return $this->post('/api/v1/licenses/deactivate', ['product_slug' => $this->productSlug, 'license_key' => $this->getLicenseKey(), 'domain' => $this->getCanonicalDomain(), 'reason' => 'wp_admin_request']); }
    public function check(bool $force = false): array
    {
        $cached = get_transient($this->cacheKey); if (!$force && is_array($cached) && !empty($cached['valid_until']) && $cached['valid_until'] > time()) return $cached;
        $response = $this->post('/api/v1/licenses/check', ['product_slug' => $this->productSlug, 'license_key' => $this->getLicenseKey(), 'domain' => $this->getCanonicalDomain()]);
        if (!empty($response['success'])) { $ttl = max(6 * HOUR_IN_SECONDS, ((int) ($response['data']['grace_period_days'] ?? 10)) * DAY_IN_SECONDS); $response['valid_until'] = time() + $ttl; set_transient($this->cacheKey, $response, $ttl); }
        return $response;
    }
    public function heartbeat(): array { return $this->post('/api/v1/licenses/heartbeat', ['product_slug' => $this->productSlug, 'license_key' => $this->getLicenseKey(), 'domain' => $this->getCanonicalDomain()]); }
    public function checkForUpdate(string $channel = 'stable'): array { return $this->post('/api/v1/updates/check', ['product_slug' => $this->productSlug, 'license_key' => $this->getLicenseKey(), 'domain' => $this->getCanonicalDomain(), 'current_version' => $this->version, 'channel' => $channel]); }
    public function registerCron(): void { if (!wp_next_scheduled($this->productSlug . '_license_heartbeat')) wp_schedule_event(time() + HOUR_IN_SECONDS, 'twicedaily', $this->productSlug . '_license_heartbeat'); add_action($this->productSlug . '_license_heartbeat', fn (): array => $this->heartbeat()); }
    public function hookUpdates(string $pluginFile): void
    {
        add_filter('pre_set_site_transient_update_plugins', function ($transient) use ($pluginFile) {
            if (!is_object($transient)) $transient = new stdClass();
            $response = $this->checkForUpdate();
            if (!empty($response['success']) && !empty($response['data']['update_available'])) {
                $transient->response[$pluginFile] = (object) ['slug' => dirname($pluginFile), 'plugin' => $pluginFile, 'new_version' => $response['data']['latest_version'], 'package' => $response['data']['download_url'], 'tested' => $response['data']['min_wordpress_version'], 'requires_php' => $response['data']['min_php_version']];
            }
            return $transient;
        });
    }
    private function post(string $path, array $payload): array
    {
        $response = wp_remote_post($this->apiBase . $path, ['timeout' => 15, 'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'], 'body' => wp_json_encode($payload)]);
        if (is_wp_error($response)) return ['success' => false, 'error' => ['code' => 'transport_error', 'message' => $response->get_error_message()]];
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : ['success' => false, 'error' => ['code' => 'invalid_response', 'message' => 'Unexpected API response.']];
    }
}
