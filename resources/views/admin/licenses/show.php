<?php
$di = static fn(?string $v): string => $v ? str_replace(' ', 'T', substr($v, 0, 16)) : '';
$allowedChannels = array_map('trim', explode(',', (string) ($license['allowed_channels'] ?? 'stable,beta')));
?>
<?php if (!$license): ?><div class="alert alert-danger">Nie znaleziono licencji.</div><?php else: ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-key me-2 text-primary"></i>Licencja #<?= e((string) $license['id']) ?></h1>
    <a href="/admin/licenses" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Powrót</a>
</div>

<div class="row g-4">
    <!-- LEWA KOLUMNA: edycja -->
    <div class="col-lg-5">
        <!-- Blok klucza -->
        <div class="card mb-4 border-0" style="background:#f8fafc">
            <div class="card-body">
                <label class="form-label fw-semibold small text-muted text-uppercase" style="letter-spacing:.05em">Klucz licencji</label>
                <div class="d-flex align-items-center gap-2">
                    <code class="fs-6 text-dark"><?= e($license['masked_key']) ?></code>
                    <span class="badge bg-secondary-subtle text-secondary">zamaskowany</span>
                </div>
                <small class="text-muted">Pełny klucz nie jest przechowywany w bazie — dostępny tylko w momencie generowania.</small>
            </div>
        </div>

        <!-- Formularz edycji -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3 fw-semibold">Szczegóły licencji</h2>
                <form method="post" action="/admin/licenses/<?= e((string) $license['id']) ?>">
                    <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                    <div class="mb-3">
                        <label class="form-label">Produkt</label>
                        <input class="form-control" value="<?= e($license['product_name']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plan</label>
                        <input class="form-control" value="<?= e($license['plan_name']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['active','inactive','expired','revoked','suspended'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= $license['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Limit aktywacji</label>
                        <input type="number" name="activation_limit" class="form-control" value="<?= e((string) $license['activation_limit']) ?>">
                        <small class="form-text text-muted">Maks. aktywnych domen. 0 = bez limitu.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail klienta</label>
                        <input type="email" name="customer_email" class="form-control" value="<?= e((string) ($license['customer_email'] ?? '')) ?>">
                    </div>

                    <!-- Opcje -->
                    <div class="mb-2">
                        <div class="form-check">
                            <input type="checkbox" name="is_lifetime" value="1" class="form-check-input" id="se_lifetime" <?= (int) $license['is_lifetime'] ? 'checked' : '' ?>>
                            <label for="se_lifetime" class="form-check-label fw-medium">Licencja lifetime</label>
                        </div>
                        <small class="form-text text-muted d-block mb-2">Licencja nigdy nie wygasa — ignoruje pola dat.</small>
                        <div class="form-check">
                            <input type="checkbox" name="updates_allowed" value="1" class="form-check-input" id="se_updates_allowed" <?= $license['updates_allowed'] ? 'checked' : '' ?>>
                            <label for="se_updates_allowed" class="form-check-label">Aktualizacje dozwolone</label>
                        </div>
                        <small class="form-text text-muted d-block mb-2">Czy licencja może pobierać aktualizacje przez API.</small>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="support_active" value="1" class="form-check-input" id="se_support_active" <?= $license['support_active'] ? 'checked' : '' ?>>
                            <label for="se_support_active" class="form-check-label">Wsparcie aktywne</label>
                        </div>
                    </div>

                    <!-- Kanały -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Dozwolone kanały</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input type="checkbox" name="allowed_channels[]" value="stable" class="form-check-input" id="se_ch_stable" <?= in_array('stable', $allowedChannels, true) ? 'checked' : '' ?>>
                                <label for="se_ch_stable" class="form-check-label">Stable</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="allowed_channels[]" value="beta" class="form-check-input" id="se_ch_beta" <?= in_array('beta', $allowedChannels, true) ? 'checked' : '' ?>>
                                <label for="se_ch_beta" class="form-check-label">Beta</label>
                            </div>
                        </div>
                        <small class="form-text text-muted">Posiadacz licencji może sam przełączać się między zaznaczonymi kanałami w ustawieniach wtyczki.</small>
                    </div>

                    <!-- Terminy -->
                    <div class="mb-3">
                        <label class="form-label">Wygasa</label>
                        <input type="datetime-local" name="expires_at" id="se_expires_at" class="form-control" value="<?= e($di($license['expires_at'] ?? null)) ?>">
                        <small class="form-text text-muted">Nieaktywne gdy licencja jest lifetime.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Aktualizacje do</label>
                        <input type="datetime-local" name="updates_expires_at" id="se_updates_expires_at" class="form-control" value="<?= e($di($license['updates_expires_at'] ?? null)) ?>">
                        <small class="form-text text-muted">Nieaktywne gdy lifetime lub aktualizacje wyłączone.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wsparcie do</label>
                        <input type="datetime-local" name="support_expires_at" id="se_support_expires_at" class="form-control" value="<?= e($di($license['support_expires_at'] ?? null)) ?>">
                        <small class="form-text text-muted">Nieaktywne gdy lifetime lub wsparcie wyłączone.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatka administratora</label>
                        <textarea name="admin_note" class="form-control" rows="3"><?= e((string) ($license['admin_note'] ?? '')) ?></textarea>
                    </div>
                    <button class="btn btn-primary">Zapisz</button>
                </form>
            </div>
        </div>

        <!-- Reguły domen -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3 fw-semibold">Reguły domen</h2>
                <form method="post" action="/admin/licenses/<?= e((string) $license['id']) ?>/domain-rules" class="row g-2 mb-3">
                    <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                    <div class="col-md-4">
                        <select name="rule_type" class="form-select form-select-sm">
                            <option value="allow">allow</option>
                            <option value="deny">deny</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <input name="pattern" class="form-control form-control-sm" placeholder="*.example.com" required>
                    </div>
                    <div class="col-12">
                        <textarea name="notes" class="form-control form-control-sm" rows="1" placeholder="Notatka (opcjonalnie)"></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-sm btn-outline-primary">Dodaj regułę</button>
                    </div>
                </form>
                <?php if ($rules): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($rules as $rule): ?>
                            <li class="list-group-item px-0 py-1 small">
                                <span class="badge <?= $rule['rule_type'] === 'allow' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>"><?= e($rule['rule_type']) ?></span>
                                <code class="ms-1"><?= e($rule['pattern']) ?></code>
                                <?php if ($rule['notes']): ?><span class="text-muted ms-1"><?= e($rule['notes']) ?></span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small mb-0">Brak reguł — wszystkie domeny są dozwolone.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- PRAWA KOLUMNA: aktywacje + historia -->
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Aktywacje domen</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Domena</th><th>URL</th><th>Status</th><th>Aktywacja</th><th>Heartbeat</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($activations as $activation): ?>
                            <tr>
                                <td><code><?= e($activation['canonical_domain']) ?></code></td>
                                <td class="text-muted small"><?= e($activation['site_url']) ?></td>
                                <td>
                                    <span class="badge <?= $activation['activation_status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                        <?= e($activation['activation_status']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= e($activation['activated_at']) ?></td>
                                <td class="small text-muted"><?= e($activation['last_checked_at']) ?></td>
                                <td>
                                    <?php if ($activation['activation_status'] === 'active'): ?>
                                        <form method="post" action="/admin/activations/<?= e((string) $activation['id']) ?>/release">
                                            <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                                            <button class="btn btn-sm btn-outline-danger">Zwolnij</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$activations): ?><tr><td colspan="6" class="text-muted small">Brak aktywacji.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Historia zmian</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Akcja</th><th>IP</th><th>Data</th></tr></thead>
                    <tbody>
                        <?php foreach ($history as $item): ?>
                            <tr>
                                <td class="small"><code><?= e($item['action']) ?></code></td>
                                <td class="small text-muted"><?= e($item['ip_address']) ?></td>
                                <td class="small text-muted"><?= e($item['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$history): ?><tr><td colspan="3" class="text-muted small">Brak wpisów.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var lifetime = document.getElementById('se_lifetime');
    var updatesAllowed = document.getElementById('se_updates_allowed');
    var supportActive = document.getElementById('se_support_active');
    var expiresAt = document.getElementById('se_expires_at');
    var updatesExpiresAt = document.getElementById('se_updates_expires_at');
    var supportExpiresAt = document.getElementById('se_support_expires_at');

    function syncDates() {
        var lt = lifetime.checked;
        var ua = updatesAllowed.checked;
        var sa = supportActive.checked;
        expiresAt.disabled = lt;
        updatesExpiresAt.disabled = lt || !ua;
        supportExpiresAt.disabled = lt || !sa;
        if (lt) { expiresAt.value = ''; updatesExpiresAt.value = ''; supportExpiresAt.value = ''; }
        else {
            if (!ua) updatesExpiresAt.value = '';
            if (!sa) supportExpiresAt.value = '';
        }
    }

    lifetime.addEventListener('change', syncDates);
    updatesAllowed.addEventListener('change', syncDates);
    supportActive.addEventListener('change', syncDates);
    syncDates();

    document.querySelector('form').addEventListener('submit', function () {
        [expiresAt, updatesExpiresAt, supportExpiresAt].forEach(function (f) { f.disabled = false; });
    });
}());
</script>
<?php endif; ?>
