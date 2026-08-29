<?php
// Prepare product plans JSON for JS — decode plans directly from product data
$productPlansJson = json_encode(array_map(static function (array $p): array {
    $plans = [];
    if (!empty($p['plans'])) {
        $decoded = json_decode((string) $p['plans'], true);
        if (is_array($decoded)) $plans = $decoded;
    }
    return ['id' => $p['id'], 'plans' => $plans];
}, $products));
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-1">Generator licencji</h1>
                <p class="text-muted mb-4 small">Wygenerowane klucze zostaną pokazane po zapisaniu — skopiuj je od razu.</p>
                <form method="post" action="/admin/licenses">
                    <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">

                    <!-- SEKCJA: Podstawowe -->
                    <h6 class="text-uppercase text-muted fw-semibold mb-3 mt-1" style="letter-spacing:.06em;font-size:.7rem">Podstawowe</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label class="form-label fw-medium">Produkt <span class="text-danger">*</span></label>
                            <select name="product_id" id="lf_product" class="form-select" required>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= e((string) $product['id']) ?>" data-plans="<?= e($product['plans'] ?? '[]') ?>"><?= e($product['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="lf_plan_wrap">
                            <label class="form-label fw-medium">Plan <span class="text-danger">*</span></label>
                            <select name="plan_name" id="lf_plan_select" class="form-select" style="display:none"></select>
                            <input name="plan_name" id="lf_plan_free" class="form-control" placeholder="np. pro" value="pro">
                            <small class="form-text text-muted">Plan przypisany do licencji. Opcje zależą od konfiguracji produktu.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Ilość</label>
                            <input type="number" min="1" max="500" name="quantity" class="form-control" value="1">
                            <small class="form-text text-muted">Max 500 na raz.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">active</option>
                                <option value="inactive">inactive</option>
                                <option value="suspended">suspended</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Limit aktywacji</label>
                            <input type="number" min="0" name="activation_limit" class="form-control" value="1">
                            <small class="form-text text-muted">Maks. aktywnych domen. 0 = bez limitu.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">E-mail klienta</label>
                            <input type="email" name="customer_email" class="form-control">
                        </div>
                    </div>

                    <!-- SEKCJA: Opcje -->
                    <h6 class="text-uppercase text-muted fw-semibold mb-3" style="letter-spacing:.06em;font-size:.7rem">Opcje</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="checkbox" name="is_lifetime" value="1" class="form-check-input" id="lf_lifetime">
                                <label for="lf_lifetime" class="form-check-label fw-medium">Licencja lifetime</label>
                            </div>
                            <small class="form-text text-muted d-block">Licencja nigdy nie wygasa — ignoruje pola dat poniżej.</small>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="checkbox" name="updates_allowed" value="1" class="form-check-input" id="lf_updates_allowed" checked>
                                <label for="lf_updates_allowed" class="form-check-label fw-medium">Aktualizacje dozwolone</label>
                            </div>
                            <small class="form-text text-muted d-block">Czy licencja może pobierać aktualizacje przez API.</small>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="checkbox" name="support_active" value="1" class="form-check-input" id="lf_support_active" checked>
                                <label for="lf_support_active" class="form-check-label fw-medium">Wsparcie aktywne</label>
                            </div>
                            <small class="form-text text-muted d-block">Czy wsparcie jest aktywne dla tej licencji.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Dozwolone kanały dystrybucji</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input type="checkbox" name="allowed_channels[]" value="stable" class="form-check-input" id="lf_ch_stable" checked>
                                    <label for="lf_ch_stable" class="form-check-label">Stable</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="allowed_channels[]" value="beta" class="form-check-input" id="lf_ch_beta" checked>
                                    <label for="lf_ch_beta" class="form-check-label">Beta</label>
                                </div>
                            </div>
                            <small class="form-text text-muted">Które kanały może używać posiadacz licencji. Odznacz Beta, żeby zablokować dostęp do wersji testowych (np. tańszy plan).</small>
                        </div>
                    </div>

                    <!-- SEKCJA: Terminy -->
                    <h6 class="text-uppercase text-muted fw-semibold mb-3" style="letter-spacing:.06em;font-size:.7rem">Terminy ważności</h6>
                    <div class="row g-3 mb-4" id="lf_dates_section">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Wygasa</label>
                            <input type="datetime-local" name="expires_at" id="lf_expires_at" class="form-control">
                            <small class="form-text text-muted">Po tej dacie licencja otrzyma status <code>expired</code>. Zostaw puste = nie wygasa.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Aktualizacje do</label>
                            <input type="datetime-local" name="updates_expires_at" id="lf_updates_expires_at" class="form-control">
                            <small class="form-text text-muted">Po tej dacie aktualizacje przestają być dostępne. Aktywne tylko gdy zaznaczono „Aktualizacje dozwolone".</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Wsparcie do</label>
                            <input type="datetime-local" name="support_expires_at" id="lf_support_expires_at" class="form-control">
                            <small class="form-text text-muted">Po tej dacie wsparcie wygasa. Aktywne tylko gdy zaznaczono „Wsparcie aktywne".</small>
                        </div>
                    </div>

                    <!-- SEKCJA: Notatka -->
                    <div class="mb-4">
                        <label class="form-label fw-medium">Notatka administratora</label>
                        <textarea name="admin_note" class="form-control" rows="3" placeholder="Widoczna tylko dla administratora..."></textarea>
                    </div>

                    <button class="btn btn-primary px-4">Generuj licencje</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var productData = <?= $productPlansJson ?>;
    var productMap = {};
    productData.forEach(function (p) { productMap[p.id] = p.plans; });

    var productSel = document.getElementById('lf_product');
    var planSel = document.getElementById('lf_plan_select');
    var planFree = document.getElementById('lf_plan_free');
    var lifetime = document.getElementById('lf_lifetime');
    var updatesAllowed = document.getElementById('lf_updates_allowed');
    var supportActive = document.getElementById('lf_support_active');
    var expiresAt = document.getElementById('lf_expires_at');
    var updatesExpiresAt = document.getElementById('lf_updates_expires_at');
    var supportExpiresAt = document.getElementById('lf_support_expires_at');

    function syncPlanSelect() {
        var pid = productSel.value;
        var plans = productMap[pid] || [];
        if (plans.length > 0) {
            planSel.innerHTML = plans.map(function (p) { return '<option value="' + p + '">' + p + '</option>'; }).join('');
            planSel.name = 'plan_name';
            planFree.name = '';
            planSel.style.display = '';
            planFree.style.display = 'none';
        } else {
            planSel.name = '';
            planFree.name = 'plan_name';
            planSel.style.display = 'none';
            planFree.style.display = '';
        }
    }

    function setDateField(field, disabled) {
        if (disabled) { field.value = ''; field.disabled = true; }
        else { field.disabled = false; }
    }

    function syncDates() {
        var lt = lifetime.checked;
        var ua = updatesAllowed.checked;
        var sa = supportActive.checked;
        setDateField(expiresAt, lt);
        setDateField(updatesExpiresAt, lt || !ua);
        setDateField(supportExpiresAt, lt || !sa);
    }

    productSel.addEventListener('change', syncPlanSelect);
    lifetime.addEventListener('change', syncDates);
    updatesAllowed.addEventListener('change', syncDates);
    supportActive.addEventListener('change', syncDates);

    syncPlanSelect();
    syncDates();

    // On submit: remove disabled attribute so values are sent (server validates)
    document.querySelector('form').addEventListener('submit', function () {
        [expiresAt, updatesExpiresAt, supportExpiresAt].forEach(function (f) { f.disabled = false; });
    });
}());
</script>
