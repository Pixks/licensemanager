<?php

declare(strict_types=1);

namespace App;

use App\Auth\AuthManager;
use App\Database\Connection;
use App\Database\Migrator;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\ForceHttpsMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\MiddlewareInterface;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Request;
use App\Http\Router;
use App\Services\ApiLogService;
use App\Services\AuditLogService;
use App\Services\DomainService;
use App\Services\DownloadTokenService;
use App\Services\LicenseKeyService;
use App\Services\LicenseService;
use App\Services\MaintenanceService;
use App\Services\ProductService;
use App\Services\RateLimiterService;
use App\Services\SecurityService;
use App\Services\SettingsService;
use App\Services\UpdateService;
use App\Services\UploadService;
use App\Services\UserService;
use App\View\View;
use PDO;

final class Application
{
    private array $config = [];
    private ?PDO $pdo = null; private ?Router $router = null; private ?AuthManager $auth = null; private ?View $view = null;
    private ?LicenseKeyService $licenseKeyService = null; private ?DomainService $domainService = null; private ?ProductService $productService = null;
    private ?AuditLogService $auditLogService = null; private ?LicenseService $licenseService = null; private ?RateLimiterService $rateLimiter = null;
    private ?SecurityService $securityService = null; private ?ApiLogService $apiLogService = null; private ?DownloadTokenService $downloadTokenService = null;
    private ?UpdateService $updateService = null; private ?UploadService $uploadService = null; private ?MaintenanceService $maintenanceService = null;
    private ?SettingsService $settingsService = null; private ?UserService $userService = null;
    public function __construct(private readonly string $basePath)
    {
        foreach (glob(config_path('*.php')) ?: [] as $file) $this->config[basename($file, '.php')] = require $file;
    }
    public function run(): void { $this->router()->dispatch(Request::fromGlobals(), $this)->send(); }
    public function config(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key); $value = $this->config;
        foreach ($segments as $segment) { if (!is_array($value) || !array_key_exists($segment, $value)) return $default; $value = $value[$segment]; }
        return $value;
    }
    public function db(): PDO { return $this->pdo ??= Connection::make($this->config['database']); }
    public function migrator(): Migrator { return new Migrator($this->db(), base_path('database/migrations')); }
    public function router(): Router
    {
        if ($this->router === null) { $this->router = new Router(); require base_path('routes/web.php'); require base_path('routes/api.php'); }
        return $this->router;
    }
    public function middleware(string $definition): MiddlewareInterface
    {
        if ($definition === 'auth') return new AuthMiddleware();
        if ($definition === 'guest') return new GuestMiddleware();
        if ($definition === 'csrf') return new CsrfMiddleware();
        if ($definition === 'force_https') return new ForceHttpsMiddleware();
        if (str_starts_with($definition, 'role:')) return new RoleMiddleware(array_map('trim', explode(',', substr($definition, 5))));
        if (str_starts_with($definition, 'rate:')) { [, $bucket, $limit, $window] = array_pad(explode(',', substr($definition, 5)), 4, null); return new RateLimitMiddleware((string) $bucket, (int) $limit, (int) $window); }
        throw new \InvalidArgumentException('Unknown middleware: ' . $definition);
    }
    public function auth(): AuthManager { return $this->auth ??= new AuthManager($this->db()); }
    public function view(): View { return $this->view ??= new View(resource_path('views')); }
    public function licenseKeyService(): LicenseKeyService { return $this->licenseKeyService ??= new LicenseKeyService((string) $this->config('security.app_key', 'development-key')); }
    public function domainService(): DomainService { return $this->domainService ??= new DomainService($this->config('security', [])); }
    public function productService(): ProductService { return $this->productService ??= new ProductService($this->db()); }
    public function auditLogService(): AuditLogService { return $this->auditLogService ??= new AuditLogService($this->db()); }
    public function licenseService(): LicenseService { return $this->licenseService ??= new LicenseService($this->db(), $this->licenseKeyService(), $this->domainService(), $this->productService(), $this->auditLogService(), $this->config('app', [])); }
    public function rateLimiter(): RateLimiterService { return $this->rateLimiter ??= new RateLimiterService(storage_path('cache')); }
    public function securityService(): SecurityService { return $this->securityService ??= new SecurityService($this->db()); }
    public function apiLogService(): ApiLogService { return $this->apiLogService ??= new ApiLogService($this->db()); }
    public function downloadTokenService(): DownloadTokenService { return $this->downloadTokenService ??= new DownloadTokenService($this->db(), (string) $this->config('security.app_key', 'development-key'), (int) $this->config('security.download_token_ttl_minutes', 15), (bool) $this->config('security.download_token_single_use', true)); }
    public function updateService(): UpdateService { return $this->updateService ??= new UpdateService($this->db(), $this->productService(), $this->licenseService(), $this->downloadTokenService(), $this->domainService(), ['url' => $this->config('app.url', '')]); }
    public function uploadService(): UploadService { $path = $this->config('app.upload_storage_path') ?: storage_path('app/private'); return $this->uploadService ??= new UploadService((string) $path, (int) $this->config('app.upload_max_bytes', 52428800)); }
    public function maintenanceService(): MaintenanceService { return $this->maintenanceService ??= new MaintenanceService($this->db(), $this->config('security', [])); }
    public function settingsService(): SettingsService { return $this->settingsService ??= new SettingsService($this->db()); }
    public function userService(): UserService { return $this->userService ??= new UserService($this->db()); }
}
