<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($stats as $label => $value): ?>
        <?php
        $icon = match(true) {
            str_contains($label, 'licencj') => 'bi-key',
            str_contains($label, 'ktywacj') => 'bi-globe',
            str_contains($label, 'rodukt') => 'bi-box-seam',
            default => 'bi-activity',
        };
        $color = match(true) {
            str_contains($label, 'licencj') => '#4361ee',
            str_contains($label, 'ktywacj') => '#10b981',
            str_contains($label, 'rodukt') => '#0ea5e9',
            default => '#f59e0b',
        };
        ?>
        <div class="col-md-3 col-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:44px;height:44px;background:<?= $color ?>1a">
                        <i class="<?= $icon ?> fs-5" style="color:<?= $color ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;font-weight:600"><?= e($label) ?></div>
                        <div class="fw-bold fs-4 lh-1 mt-1"><?= e((string) $value) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-activity text-muted"></i>
        <span class="fw-semibold">Ostatnie żądania API</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>ID</th><th>Metoda</th><th>Ścieżka</th><th>Status</th><th>Produkt</th><th>Domena</th><th>Data</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td class="text-muted small"><?= e((string) $log['id']) ?></td>
                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($log['request_method']) ?></span></td>
                        <td class="small"><code><?= e($log['request_path']) ?></code></td>
                        <td>
                            <span class="badge <?= (int) $log['response_status'] < 400 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                <?= e((string) $log['response_status']) ?>
                            </span>
                        </td>
                        <td class="small"><?= e($log['product_slug']) ?></td>
                        <td class="small text-muted"><?= e($log['domain']) ?></td>
                        <td class="small text-muted"><?= e($log['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentLogs): ?><tr><td colspan="7" class="text-center text-muted py-3 small">Brak żądań API.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
