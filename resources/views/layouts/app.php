<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? $app->config('app.name')) ?> — <?= e($app->config('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/app.css" rel="stylesheet">
</head>
<body>
<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$navActive = static function (string $prefix) use ($currentPath): string {
    return str_starts_with($currentPath, $prefix) ? 'active' : '';
};
?>
<div class="app-layout">
    <?php if ($authUser): ?>
    <nav class="sidebar d-flex flex-column" id="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-shield-check me-2"></i><?= e($app->config('app.name')) ?>
        </div>
        <ul class="sidebar-nav">
            <li><a href="/admin" class="sidebar-link <?= $currentPath === '/admin' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a></li>
            <li><a href="/admin/products" class="sidebar-link <?= $navActive('/admin/products') ?>">
                <i class="bi bi-box-seam"></i><span>Produkty</span>
            </a></li>
            <li><a href="/admin/licenses" class="sidebar-link <?= $navActive('/admin/licenses') ?>">
                <i class="bi bi-key"></i><span>Licencje</span>
            </a></li>
            <li><a href="/admin/activations" class="sidebar-link <?= $navActive('/admin/activations') ?>">
                <i class="bi bi-globe"></i><span>Aktywacje</span>
            </a></li>
            <li><a href="/admin/logs" class="sidebar-link <?= $navActive('/admin/logs') ?>">
                <i class="bi bi-journal-text"></i><span>Logi</span>
            </a></li>
            <?php if (in_array('superadmin', $authRoles, true)): ?>
            <li><a href="/admin/users" class="sidebar-link <?= $navActive('/admin/users') ?>">
                <i class="bi bi-people"></i><span>Użytkownicy</span>
            </a></li>
            <?php endif; ?>
            <?php if (array_intersect(['superadmin', 'admin'], $authRoles)): ?>
            <li><a href="/admin/settings" class="sidebar-link <?= $navActive('/admin/settings') ?>">
                <i class="bi bi-gear"></i><span>Ustawienia</span>
            </a></li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <i class="bi bi-person-circle me-2"></i>
                <span class="text-truncate"><?= e($authUser['name']) ?></span>
            </div>
            <form method="post" action="/logout" class="mt-2">
                <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                <button class="btn btn-outline-light btn-sm w-100"><i class="bi bi-box-arrow-right me-1"></i>Wyloguj</button>
            </form>
        </div>
    </nav>
    <?php endif; ?>

    <div class="main-content">
        <?php if ($authUser): ?>
        <div class="topbar d-flex align-items-center justify-content-between px-4 py-2">
            <button class="btn btn-sm btn-link text-muted p-0 d-lg-none" id="sidebar-toggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h6 class="mb-0 fw-semibold text-dark"><?= e($pageTitle ?? '') ?></h6>
            <div></div>
        </div>
        <?php endif; ?>

        <main class="content-area px-4 py-4">
            <?php if (!empty($flash['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                    <span><?= e($flash['success']) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <span><?= e($flash['error']) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    var toggle = document.getElementById('sidebar-toggle');
    var sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-open');
        });
    }
}());
</script>
</body>
</html>
