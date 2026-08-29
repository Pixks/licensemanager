<?php
$productPlansStr = '';
if (!empty($product['plans'])) {
    $decoded = json_decode((string) $product['plans'], true);
    if (is_array($decoded)) $productPlansStr = implode(', ', $decoded);
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-box-seam me-2"></i><?= $product ? 'Produkt: ' . e($product['name']) : 'Nowy produkt' ?></h1>
    <a href="/admin/products" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Powrót</a>
</div>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 fw-semibold mb-3">Dane produktu</h2>
                <form method="post" action="<?= $product ? '/admin/products/' . e((string) $product['id']) : '/admin/products' ?>">
                    <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                    <div class="mb-3">
                        <label class="form-label">Nazwa</label>
                        <input name="name" class="form-control" required value="<?= e($product['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug / product_id</label>
                        <input name="slug" class="form-control" required value="<?= e($product['slug'] ?? '') ?>">
                        <small class="form-text text-muted">Używany w URL API, np. <code>/api/v1/{slug}/check</code>. Tylko małe litery, cyfry i myślniki.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opis</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($product['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Aktualna wersja</label>
                        <input name="current_version" class="form-control" value="<?= e($product['current_version'] ?? '') ?>">
                        <small class="form-text text-muted">Aktualizowana automatycznie gdy wersja jest publikowana.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Domyślny kanał</label>
                        <select name="default_channel" class="form-select">
                            <option value="stable" <?= ($product['default_channel'] ?? 'stable') === 'stable' ? 'selected' : '' ?>>stable</option>
                            <option value="beta" <?= ($product['default_channel'] ?? '') === 'beta' ? 'selected' : '' ?>>beta</option>
                        </select>
                        <small class="form-text text-muted">Kanał używany przy automatycznym aktualizowaniu <code>current_version</code>.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plany licencji</label>
                        <input name="plans_input" class="form-control" value="<?= e($productPlansStr) ?>" placeholder="np. starter, pro, enterprise">
                        <small class="form-text text-muted">Lista planów oddzielona przecinkami. Przy tworzeniu licencji plan będzie wybierany z tej listy. Zostaw puste aby nie ograniczać.</small>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" <?= !isset($product['is_active']) || $product['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label">Produkt aktywny</label>
                    </div>
                    <button class="btn btn-primary">Zapisz produkt</button>
                </form>
            </div>
        </div>

        <?php if ($product): ?>
        <div class="card shadow-sm border-danger mt-4">
            <div class="card-body">
                <h2 class="h6 text-danger fw-semibold">Usuń produkt</h2>
                <p class="text-muted small mb-3">Usunięcie oznacza miękkie ukrycie produktu i jego wersji. Akcja jest blokowana, jeśli do produktu są przypisane licencje.</p>
                <form method="post" action="/admin/products/<?= e((string) $product['id']) ?>/delete">
                    <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                    <button class="btn btn-outline-danger btn-sm" <?= !$canDeleteProduct ? 'disabled' : '' ?>>Usuń produkt</button>
                    <?php if (!$canDeleteProduct): ?><div class="form-text text-danger mt-1">Najpierw usuń lub odłącz powiązane licencje.</div><?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($product): ?>
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 fw-semibold mb-3"><?= $editingVersion ? 'Edytuj wersję' : 'Dodaj wersję' ?></h2>
                <form method="post" enctype="multipart/form-data" action="<?= $editingVersion ? '/admin/products/' . e((string) $product['id']) . '/versions/' . e((string) $editingVersion['id']) : '/admin/products/' . e((string) $product['id']) . '/versions' ?>">
                    <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Wersja</label>
                            <input name="version" class="form-control" placeholder="1.0.0" required value="<?= e($editingVersion['version'] ?? '') ?>">
                            <small class="form-text text-muted">Format semver, np. <code>1.2.3</code>.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data publikacji</label>
                            <input type="datetime-local" name="published_at" class="form-control" value="<?= e(!empty($editingVersion['published_at']) ? date('Y-m-d\TH:i', strtotime((string) $editingVersion['published_at'])) : '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kanał</label>
                            <select name="channel" class="form-select">
                                <option value="stable" <?= ($editingVersion['channel'] ?? 'stable') === 'stable' ? 'selected' : '' ?>>stable</option>
                                <option value="beta" <?= ($editingVersion['channel'] ?? '') === 'beta' ? 'selected' : '' ?>>beta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="release_status" class="form-select">
                                <option value="draft" <?= ($editingVersion['release_status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>draft</option>
                                <option value="published" <?= ($editingVersion['release_status'] ?? '') === 'published' ? 'selected' : '' ?>>published</option>
                                <option value="archived" <?= ($editingVersion['release_status'] ?? '') === 'archived' ? 'selected' : '' ?>>archived</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Min. WordPress</label>
                            <input name="min_wordpress_version" class="form-control" placeholder="6.0" value="<?= e($editingVersion['min_wordpress_version'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Min. PHP</label>
                            <input name="min_php_version" class="form-control" placeholder="8.2" value="<?= e($editingVersion['min_php_version'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Changelog</label>
                            <textarea name="changelog" class="form-control" rows="4"><?= e($editingVersion['changelog'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Plik ZIP<?= $editingVersion ? ' (opcjonalnie — wgraj nowy aby zastąpić)' : '' ?></label>
                            <input type="file" name="zip_file" accept=".zip,application/zip" class="form-control" <?= $editingVersion ? '' : 'required' ?>>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-primary"><?= $editingVersion ? 'Zapisz wersję' : 'Dodaj wersję' ?></button>
                        <?php if ($editingVersion): ?><a href="/admin/products/<?= e((string) $product['id']) ?>" class="btn btn-outline-secondary">Anuluj edycję</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Historia wersji</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Wersja</th><th>Kanał</th><th>Status</th><th>Publikacja</th><th>SHA-256</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($versions as $version): ?>
                            <tr>
                                <td><?= e($version['version']) ?></td>
                                <td>
                                    <span class="badge <?= $version['channel'] === 'beta' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' ?>">
                                        <?= e($version['channel']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $version['release_status'] === 'published' ? 'bg-success-subtle text-success' : ($version['release_status'] === 'archived' ? 'bg-secondary-subtle text-secondary' : 'bg-info-subtle text-info') ?>">
                                        <?= e($version['release_status']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= e($version['published_at']) ?></td>
                                <td><code class="small"><?= e(substr((string) $version['sha256_hash'], 0, 12)) ?>...</code></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="/admin/products/<?= e((string) $product['id']) ?>?edit_version=<?= e((string) $version['id']) ?>" class="btn btn-sm btn-outline-primary">Edytuj</a>
                                        <form method="post" action="/admin/products/<?= e((string) $product['id']) ?>/versions/<?= e((string) $version['id']) ?>/delete">
                                            <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                                            <button class="btn btn-sm btn-outline-danger">Usuń</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$versions): ?><tr><td colspan="6" class="text-muted small text-center py-3">Brak wersji.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

