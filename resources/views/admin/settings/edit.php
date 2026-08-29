<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="d-flex align-items-center gap-2 mb-4">
            <h1 class="h4 mb-0"><i class="bi bi-gear me-2"></i>Ustawienia systemowe</h1>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="/admin/settings">
                    <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                    <div class="mb-4">
                        <label class="form-label fw-medium">Grace period (dni)</label>
                        <input name="grace_period_days" type="number" min="0" class="form-control" style="max-width:160px"
                               value="<?= e($settings['grace_period_days'] ?? (string) $app->config('app.grace_period_days', 10)) ?>">
                        <small class="form-text text-muted">
                            Liczba dni po wygaśnięciu licencji, przez które licencja nadal działa (grace period).
                            Ustawienie 0 = brak grace period — licencja wygasa natychmiastowo.
                            Wartość jest zwracana przez API jako <code>grace_period_days</code>.
                        </small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">E-mail powiadomień</label>
                        <input name="notification_email" type="email" class="form-control" style="max-width:320px"
                               value="<?= e($settings['notification_email'] ?? '') ?>">
                        <small class="form-text text-muted">Adres do powiadomień systemowych (opcjonalnie).</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Domyślny kanał aktualizacji</label>
                        <select name="default_channel" class="form-select" style="max-width:200px">
                            <option value="stable" <?= ($settings['default_channel'] ?? 'stable') === 'stable' ? 'selected' : '' ?>>stable</option>
                            <option value="beta" <?= ($settings['default_channel'] ?? '') === 'beta' ? 'selected' : '' ?>>beta</option>
                        </select>
                        <small class="form-text text-muted">Domyślny kanał dla nowych produktów i żądań API bez jawnie podanego kanału.</small>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Zapisz ustawienia</button>
                </form>
            </div>
        </div>
    </div>
</div>
