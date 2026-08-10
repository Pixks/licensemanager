<h1 class="h3 mb-4">Dashboard</h1>
<div class="row g-3 mb-4">
    <?php foreach ($stats as $label => $value): ?>
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted text-uppercase small"><?= e($label) ?></div><div class="display-6 fw-bold"><?= e((string) $value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>
<div class="card shadow-sm"><div class="card-header">Ostatnie żądania API</div><div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>ID</th><th>Metoda</th><th>Ścieżka</th><th>Status</th><th>Produkt</th><th>Domena</th><th>Data</th></tr></thead><tbody><?php foreach ($recentLogs as $log): ?><tr><td><?= e((string) $log['id']) ?></td><td><?= e($log['request_method']) ?></td><td><?= e($log['request_path']) ?></td><td><?= e((string) $log['response_status']) ?></td><td><?= e($log['product_slug']) ?></td><td><?= e($log['domain']) ?></td><td><?= e($log['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
