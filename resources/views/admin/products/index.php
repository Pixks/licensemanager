<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-box-seam me-2"></i>Produkty</h1>
    <a href="/admin/products/create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nowy produkt</a>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Nazwa</th><th>Slug</th><th>Wersja</th><th>Kanał</th><th>Plany</th><th>Status</th><th>Wersje</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <?php
                    $plans = [];
                    if (!empty($product['plans'])) {
                        $decoded = json_decode((string) $product['plans'], true);
                        if (is_array($decoded)) $plans = $decoded;
                    }
                    ?>
                    <tr>
                        <td class="fw-medium"><?= e($product['name']) ?></td>
                        <td><code class="small"><?= e($product['slug']) ?></code></td>
                        <td class="small"><?= e($product['current_version'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= $product['default_channel'] === 'beta' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' ?>">
                                <?= e($product['default_channel']) ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?php if ($plans): ?>
                                <?php foreach ($plans as $plan): ?>
                                    <span class="badge bg-secondary-subtle text-secondary me-1"><?= e($plan) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $product['is_active'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                <?= $product['is_active'] ? 'Aktywny' : 'Nieaktywny' ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= e((string) $product['versions_count']) ?></td>
                        <td><a href="/admin/products/<?= e((string) $product['id']) ?>" class="btn btn-sm btn-outline-primary">Szczegóły</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$products): ?><tr><td colspan="8" class="text-center text-muted py-4">Brak produktów.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
