# Instrukcje dla dewelopera / AI — License Manager Update

> Ten dokument to kompletna instrukcja techniczna dla programisty lub bota AI wykonującego zmiany w repozytorium `Pixks/licensemanager`.  
> Przeczytaj cały dokument PRZED rozpoczęciem pracy.

---

## 1. Kontekst repozytorium

### Stack techniczny
- **PHP 8.2+**, bez frameworka (własny mini-framework)
- **SQLite** (development) / **MySQL 8+** (production) — kod musi działać na obu
- **Bootstrap 5.3.3** (CDN) — UI framework
- **Bootstrap Icons 1.11.x** (CDN) — do dodania przy redesignie UI
- Brak npm/webpack — vanilla JS, brak bundlera
- Brak ORM — surowe PDO + własne `BaseModel`

### Struktura katalogów
```
src/
  Controllers/Admin/    — kontrolery panelu admina
  Controllers/Api/      — kontrolery API dla wtyczek
  Models/               — modele (cienka warstwa PDO)
  Services/             — logika biznesowa (tu jest większość kodu)
  Validation/           — walidator
database/migrations/    — migracje (każda to plik PHP zwracający anonimową klasę)
resources/views/admin/  — widoki PHP (nie Twig/Blade, czysty PHP)
public/assets/          — CSS/JS statyczne
docs/                   — dokumentacja
```

### Konwencje kodu
- `declare(strict_types=1)` w każdym pliku PHP
- Klasy `final` wszędzie gdzie możliwe
- Metody jednolinijkowe w kontrolerach to zamierzony styl — **nie przepisuj**
- Widoki PHP — proste `echo`, escapowanie przez `e($value)`
- `BaseModel::create()`, `BaseModel::updateById()`, `BaseModel::find()` — API modeli
- Brak dependency injection container — zależności przez `$this->app->xxx()` w kontrolerach
- Nazwy migracji: `YYYYMMDD_NNNNNN_description.php`

---

## 2. Jak działają migracje

Każda migracja to plik w `database/migrations/` z anonimową klasą:

```php
<?php
declare(strict_types=1);
use App\Database\Migration;
return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        // SQLite/MySQL compatible — używaj helper metod z Migration:
        // $this->stringType(120), $this->boolType(), $this->textType()
        // $this->dateTimeType(), $this->idColumn($pdo), $this->foreignId($pdo)
        // $this->createdUpdated($pdo), $this->softDeletes()
        $this->exec($pdo, 'ALTER TABLE licenses ADD COLUMN ...');
    }
};
```

**WAŻNE:** Dla `ALTER TABLE` z nową kolumną w SQLite użyj:
```php
$this->exec($pdo, 'ALTER TABLE licenses ADD COLUMN allowed_channels VARCHAR(40) NOT NULL DEFAULT "stable,beta"');
```
SQLite obsługuje `ADD COLUMN` ale **nie** `DROP COLUMN` przed wersją 3.35. Sprawdź czy helper `Migration` ma metody dla obu silników. Jeśli kolumna może nie istnieć — używaj `try/catch` lub `PRAGMA table_info`.

---

## 3. Zadania do wykonania (z `UPDATE_PLAN.md`)

### Faza 1 — Naprawy krytyczne

#### Zadanie B: `updatesAllowed()` — ignoruj daty dla lifetime
**Plik:** `src/Services/LicenseService.php`  
**Metoda:** `updatesAllowed(array $license): bool`

Obecny kod:
```php
if ((int) $license['is_lifetime'] === 1) return !in_array($this->statusForLicense($license), ['revoked', 'suspended'], true);
```
To jest już prawidłowe w tym miejscu. Sprawdź też czy `statusForLicense()` nie blokuje przez daty.  
→ Dodaj analogiczną logikę dla support jeśli istnieje metoda `supportActive()`.

#### Zadanie F: NULL safety w `show.php`
**Plik:** `resources/views/admin/licenses/show.php`  
**Problem:** Każde miejsce z `str_replace(' ', 'T', substr((string) $license['expires_at'], 0, 16))`

Zamień na helper lub inline:
```php
$dateToInput = static fn(?string $v): string => $v ? str_replace(' ', 'T', substr($v, 0, 16)) : '';
```
Wywołuj: `<?= e($dateToInput($license['expires_at'])) ?>`

#### Zadanie G: Dodać `is_lifetime` do formularza edycji
**Plik:** `resources/views/admin/licenses/show.php`  
**Plik:** `src/Controllers/Admin/LicenseController.php` → `updateStatus()`

W widoku dodać checkbox `is_lifetime` analogicznie do `updates_allowed`.  
W controllerze dodać do tablicy aktualizacji: `'is_lifetime' => $request->input('is_lifetime') === '1' ? 1 : 0`

---

### Faza 2 — Kluczowe funkcje

#### Zadanie 1: Wyświetlanie kluczy po wygenerowaniu
**Plik:** `src/Controllers/Admin/LicenseController.php` → `store()`  
**Plik:** `resources/views/admin/licenses/index.php`

W `store()` sesja już jest ustawiana: `$_SESSION['_generated_licenses']`.  
W `index()` — odczytać sesję i przekazać do widoku jako `$generatedKeys`:
```php
$generatedKeys = $_SESSION['_generated_licenses'] ?? [];
unset($_SESSION['_generated_licenses']);
```

W widoku `index.php` — jeśli `$generatedKeys` niepuste, wyświetlić kartę:
```html
<div class="card border-success mb-4">
  <div class="card-header bg-success text-white">Wygenerowane klucze — zapisz je teraz!</div>
  <div class="card-body">
    <table>...lista kluczy z przyciskiem kopiowania...</table>
  </div>
</div>
```
JS kopiowania:
```javascript
navigator.clipboard.writeText(key).then(() => btn.textContent = 'Skopiowano!');
```

#### Zadanie 5: Lifetime blokowanie pól

**JavaScript** — dodać do `form.php` i `show.php`:
```javascript
document.addEventListener('DOMContentLoaded', function() {
    const lifetime = document.getElementById('is_lifetime');
    const updatesAllowed = document.getElementById('updates_allowed');
    const supportActive = document.getElementById('support_active');

    function syncFields() {
        const isLifetime = lifetime?.checked;
        const hasUpdates = updatesAllowed?.checked;
        const hasSupport = supportActive?.checked;

        ['expires_at','updates_expires_at','support_expires_at'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if (isLifetime) { el.value = ''; el.disabled = true; }
            else el.disabled = false;
        });

        const updExp = document.getElementById('updates_expires_at');
        if (updExp && !isLifetime) {
            if (!hasUpdates) { updExp.value = ''; updExp.disabled = true; }
            else updExp.disabled = false;
        }

        const supExp = document.getElementById('support_expires_at');
        if (supExp && !isLifetime) {
            if (!hasSupport) { supExp.value = ''; supExp.disabled = true; }
            else supExp.disabled = false;
        }
    }

    lifetime?.addEventListener('change', syncFields);
    updatesAllowed?.addEventListener('change', syncFields);
    supportActive?.addEventListener('change', syncFields);
    syncFields();
});
```

**PHP server-side** w `LicenseService::generateLicenses()`:
```php
if (!empty($data['is_lifetime'])) {
    $data['expires_at'] = null;
    $data['updates_expires_at'] = null;
    $data['support_expires_at'] = null;
}
```

#### Zadanie 3: Kanał dystrybucji

**Migracja** `20260810_000005_add_channel_to_licenses.php`:
```sql
ALTER TABLE licenses ADD COLUMN allowed_channels VARCHAR(40) NOT NULL DEFAULT 'stable,beta'
```

**API — zmiany w kontrolerach Api/:**  
Przy `activate`, `check`, `heartbeat`, `update` — odczytać `$request->input('channel', 'stable')`, walidować wobec `$license['allowed_channels']`.

**Walidacja:**
```php
$requestedChannel = $request->input('channel', 'stable');
$allowed = explode(',', $license['allowed_channels'] ?? 'stable,beta');
if (!in_array($requestedChannel, $allowed, true)) {
    // zwróć 403 channel_not_allowed lub fallback do stable
    $requestedChannel = 'stable';
}
```

**Odpowiedź API** — dodać pole `"channel": "stable"` do wszystkich odpowiedzi.

**Panel admina** — w `form.php` i `show.php` dodać checkboxy:
```html
<label>Dozwolone kanały</label>
<div class="form-check">
    <input type="checkbox" name="allowed_channels[]" value="stable" 
           <?= str_contains($license['allowed_channels'] ?? 'stable,beta', 'stable') ? 'checked' : '' ?>>
    <label>Stable</label>
</div>
<div class="form-check">
    <input type="checkbox" name="allowed_channels[]" value="beta"
           <?= str_contains($license['allowed_channels'] ?? 'stable,beta', 'beta') ? 'checked' : '' ?>>
    <label>Beta</label>
</div>
```

W controllerze — skleić wartości: `implode(',', $request->input('allowed_channels', ['stable']))`.

#### Zadanie 4: Plany z listy produktu

**Migracja** `20260810_000006_add_plans_to_products.php`:
```sql
ALTER TABLE products ADD COLUMN plans TEXT NULL
```
Wartość JSON: `["starter","pro","enterprise"]` lub `null` (brak ograniczeń).

**ProductService:** w `createProduct()` i `updateProduct()` → `'plans' => $data['plans'] ?? null`.  
Przy odczycie: `json_decode($product['plans'] ?? 'null', true) ?? []`.

**Formularz produktu** — pole planów:
```html
<label>Plany licencji (oddzielone przecinkami)</label>
<input name="plans_input" class="form-control" value="<?= e(implode(',', json_decode($product['plans'] ?? '[]', true))) ?>">
<small>np. starter,pro,enterprise — zostaw puste aby nie ograniczać</small>
```
W controllerze konwertować: `'plans' => $request->input('plans_input') ? json_encode(array_map('trim', explode(',', $request->input('plans_input')))) : null`.

**Formularz licencji** — dynamiczny select:
```html
<select name="product_id" id="product_select" class="form-select">
    <?php foreach ($products as $product): ?>
    <option value="<?= e($product['id']) ?>" 
            data-plans="<?= e($product['plans'] ?? '[]') ?>">
        <?= e($product['name']) ?>
    </option>
    <?php endforeach; ?>
</select>

<select name="plan_name" id="plan_select" class="form-select"></select>
<input name="plan_name_free" id="plan_free" class="form-control" style="display:none" placeholder="Nazwa planu">
```

JS:
```javascript
document.getElementById('product_select').addEventListener('change', function() {
    const plans = JSON.parse(this.selectedOptions[0].dataset.plans || '[]');
    const sel = document.getElementById('plan_select');
    const free = document.getElementById('plan_free');
    if (plans.length > 0) {
        sel.style.display = '';
        free.style.display = 'none';
        sel.innerHTML = plans.map(p => `<option value="${p}">${p}</option>`).join('');
        sel.name = 'plan_name';
        free.name = '';
    } else {
        sel.style.display = 'none';
        free.style.display = '';
        sel.name = '';
        free.name = 'plan_name';
    }
});
document.getElementById('product_select').dispatchEvent(new Event('change'));
```

---

### Faza 3 — Redesign UI

Przy redesignie **zachowaj** całą logikę PHP — zmieniaj tylko HTML/CSS/strukturę.

**Sidebar layout** — zastąpić `<nav class="navbar...">` w `layouts/app.php`:
```html
<div class="d-flex" style="min-height:100vh">
    <!-- Sidebar -->
    <nav class="sidebar d-flex flex-column p-3" style="width:240px;background:#1e2235;min-height:100vh">
        <a href="/admin" class="sidebar-brand text-white text-decoration-none mb-4 fs-5 fw-bold">
            <?= e($app->config('app.name')) ?>
        </a>
        <ul class="nav flex-column gap-1">
            <li><a href="/admin/products" class="nav-link text-white-50"><i class="bi bi-box-seam me-2"></i>Produkty</a></li>
            <li><a href="/admin/licenses" class="nav-link text-white-50"><i class="bi bi-key me-2"></i>Licencje</a></li>
            <!-- ... -->
        </ul>
        <div class="mt-auto">
            <!-- user info + logout -->
        </div>
    </nav>
    <!-- Main content -->
    <main class="flex-grow-1 p-4 bg-light">
        <!-- flash messages + content -->
    </main>
</div>
```

Dodać do `<head>`:
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
```

**`public/assets/app.css`** — dodać:
```css
:root {
    --color-sidebar: #1e2235;
    --color-primary: #4361ee;
    --radius-card: 12px;
}
.card { border-radius: var(--radius-card); border: none; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.badge-active { background: #d1fae5; color: #065f46; }
.badge-expired { background: #fee2e2; color: #991b1b; }
.badge-suspended { background: #fef9c3; color: #854d0e; }
.badge-inactive { background: #f1f5f9; color: #475569; }
.sidebar .nav-link:hover { background: rgba(255,255,255,.08); border-radius: 8px; color: white !important; }
.sidebar .nav-link.active { background: var(--color-primary); color: white !important; border-radius: 8px; }
```

---

## 4. Ważne zasady podczas implementacji

1. **Każda zmiana SQL musi działać zarówno w SQLite jak i MySQL** — używaj helper metod z `Migration`.
2. **Nie zmieniaj struktury metod kontrolerów** bez potrzeby — styl jednolinijkowy jest zamierzony.
3. **Widoki nie mają Twig/Blade** — tylko PHP. `echo e($value)` do escapowania.
4. **Disabled pola w HTML formularzy NIE są wysyłane** — przy blokowania pól przez JS użyj `readonly` + wyczyść wartość, lub dodaj hidden input z pustą wartością. Najlepsza praktyka: czyść wartość w JS przed submitem lub obsłuż server-side.
5. **Migracje są nieodwracalne** w tym systemie — nie ma metody `down()`. Planuj zmiany ostrożnie.
6. **Testy** — uruchamiaj przez `./vendor/bin/phpunit` lub `composer test` jeśli dostępne.
7. **Commit messages** — po polsku lub angielsku, opisowe.

---

## 5. Kolejność plików do modyfikacji

```
Faza 1 (błędy):
1. src/Services/LicenseService.php
2. resources/views/admin/licenses/show.php

Faza 2 (funkcje):
3. database/migrations/20260810_000005_add_channel_to_licenses.php  [NOWY]
4. database/migrations/20260810_000006_add_plans_to_products.php    [NOWY]
5. src/Services/LicenseService.php
6. src/Services/ProductService.php
7. src/Controllers/Admin/LicenseController.php
8. src/Controllers/Admin/ProductController.php
9. src/Controllers/Api/*.php
10. resources/views/admin/licenses/form.php
11. resources/views/admin/licenses/show.php
12. resources/views/admin/products/form.php

Faza 3 (UI):
13. resources/views/layouts/app.php
14. public/assets/app.css
15. resources/views/admin/dashboard.php
16. resources/views/admin/licenses/index.php
17. resources/views/admin/products/index.php
18. resources/views/auth/*.php
```

---

## 6. Jak uruchomić lokalnie (weryfikacja zmian)

```bash
cd /path/to/licensemanager
composer install
cp .env.example .env
# Edytuj .env: ustaw APP_URL, DB_PATH (SQLite)
php bin/console migrate
php bin/console seed
php -S localhost:8080 -t public/
```

Otwórz `http://localhost:8080/admin` — dane logowania z seedera.
