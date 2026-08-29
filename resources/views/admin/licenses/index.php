<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-key me-2"></i>Licencje</h1>
    <div class="d-flex gap-2">
        <a href="/admin/licenses/export" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Eksport CSV</a>
        <a href="/admin/licenses/create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Generator licencji</a>
    </div>
</div>

<?php if (!empty($_SESSION['_generated_licenses'])): $generated = $_SESSION['_generated_licenses']; unset($_SESSION['_generated_licenses']); ?>
<div class="card border-success mb-4">
    <div class="card-header bg-success text-white fw-semibold d-flex align-items-center gap-2">
        <i class="bi bi-check-circle"></i> Wygenerowane klucze — skopiuj je teraz!
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">Pełne klucze nie są przechowywane w bazie — to jedyna okazja by je zobaczyć.</p>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Pełny klucz</th><th>Zamaskowany (panel)</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($generated as $item): ?>
                        <tr>
                            <td><code id="key-<?= e(md5($item['plain_key'])) ?>"><?= e($item['plain_key']) ?></code></td>
                            <td class="text-muted small"><?= e($item['masked_key']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary copy-btn" data-key="<?= e($item['plain_key']) ?>">
                                    <i class="bi bi-clipboard"></i> Kopiuj
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.copy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var key = btn.getAttribute('data-key');
        navigator.clipboard.writeText(key).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check"></i> Skopiowano!';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-secondary');
            setTimeout(function() { btn.innerHTML = orig; btn.classList.remove('btn-success'); btn.classList.add('btn-outline-secondary'); }, 2000);
        });
    });
});
</script>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Produkt</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">Wszystkie</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= e((string) $product['id']) ?>" <?= (($filters['product_id'] ?? '') == $product['id']) ? 'selected' : '' ?>><?= e($product['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Wszystkie</option>
                    <?php foreach (['active','inactive','expired','revoked','suspended'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Domena</label>
                <input name="domain" class="form-control form-control-sm" value="<?= e($filters['domain'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">E-mail</label>
                <input name="customer_email" class="form-control form-control-sm" value="<?= e($filters['customer_email'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Fragment klucza</label>
                <input name="key_fragment" class="form-control form-control-sm" value="<?= e($filters['key_fragment'] ?? '') ?>">
            </div>
            <div class="col-md-1">
                <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>ID</th><th>Produkt</th><th>Klucz</th><th>Status</th><th>E-mail</th><th>Aktywacje</th><th>Wygasa</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license): ?>
                    <?php
                        $statusClass = match($license['status']) {
                            'active'    => 'bg-success-subtle text-success',
                            'expired'   => 'bg-danger-subtle text-danger',
                            'suspended' => 'bg-warning-subtle text-warning',
                            'revoked'   => 'bg-danger-subtle text-danger',
                            default     => 'bg-secondary-subtle text-secondary',
                        };
                    ?>
                    <tr>
                        <td class="text-muted small"><?= e((string) $license['id']) ?></td>
                        <td><?= e($license['product_name']) ?></td>
                        <td><code class="small"><?= e($license['masked_key']) ?></code></td>
                        <td><span class="badge <?= $statusClass ?>"><?= e($license['status']) ?></span></td>
                        <td class="small text-muted"><?= e($license['customer_email'] ?? '') ?></td>
                        <td class="small"><?= e((string) $license['activations_in_use']) ?> / <?= e($license['activation_limit'] == 0 ? '∞' : (string) $license['activation_limit']) ?></td>
                        <td class="small text-muted"><?= $license['is_lifetime'] ? '<span class="badge bg-info-subtle text-info">lifetime</span>' : e($license['expires_at'] ?? '—') ?></td>
                        <td><a href="/admin/licenses/<?= e((string) $license['id']) ?>" class="btn btn-sm btn-outline-primary">Szczegóły</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$licenses): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Brak licencji spełniających kryteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
