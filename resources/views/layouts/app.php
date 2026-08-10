<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? $app->config('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/admin"><?= e($app->config('app.name')) ?></a>
        <?php if ($authUser): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExampleDefault"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarsExampleDefault">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="/admin/products">Produkty</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/licenses">Licencje</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/activations">Aktywacje</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/logs">Logi</a></li>
                    <?php if (in_array('superadmin', $authRoles, true)): ?><li class="nav-item"><a class="nav-link" href="/admin/users">Użytkownicy</a></li><?php endif; ?>
                    <?php if (array_intersect(['superadmin', 'admin'], $authRoles)): ?><li class="nav-item"><a class="nav-link" href="/admin/settings">Ustawienia</a></li><?php endif; ?>
                </ul>
                <div class="d-flex align-items-center gap-3 text-white">
                    <span><?= e($authUser['name']) ?></span>
                    <form method="post" action="/logout" class="m-0">
                        <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                        <button class="btn btn-outline-light btn-sm">Wyloguj</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>
<main class="container py-4">
    <?php if (!empty($flash['success'])): ?><div class="alert alert-success"><?= e($flash['success']) ?></div><?php endif; ?>
    <?php if (!empty($flash['error'])): ?><div class="alert alert-danger"><pre class="mb-0 text-wrap"><?= e($flash['error']) ?></pre></div><?php endif; ?>
    <?= $content ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
