# Plan aktualizacji systemu License Manager

> Wersja planu: 1.0 | Data: 2026-08-29  
> Repozytorium: `Pixks/licensemanager` | Stack: PHP 8.2, SQLite/MySQL, Bootstrap 5

---

## Spis treści

1. [Wyświetlanie klucza licencji po wygenerowaniu](#1-wyświetlanie-klucza-licencji-po-wygenerowaniu)
2. [Opisy pól — tooltips i help text](#2-opisy-pól--tooltips-i-help-text)
3. [Przełączanie kanału dystrybucji przez użytkownika](#3-przełączanie-kanału-dystrybucji-przez-użytkownika)
4. [Plany licencji z listy konfigurowanej w produkcie](#4-plany-licencji-z-listy-konfigurowanej-w-produkcie)
5. [Lifetime — blokowanie pól przy zaznaczeniu](#5-lifetime--blokowanie-pól-przy-zaznaczeniu)
6. [Redesign UI — nowoczesny wygląd blokowy](#6-redesign-ui--nowoczesny-wygląd-blokowy)
7. [Naprawy błędów logicznych](#7-naprawy-błędów-logicznych)

---

## 1. Wyświetlanie klucza licencji po wygenerowaniu

### Problem
- Klucze są generowane i zapisywane do `$_SESSION['_generated_licenses']`, ale nigdy nie są wyświetlane użytkownikowi po przekierowaniu.
- W widoku `show.php` pole „Klucz" pokazuje tylko zamaskowany klucz (`masked_key`), a pełnego klucza nie można odtworzyć z bazy (przechowywany jest tylko hash SHA-256).

### Rozwiązanie

**Po wygenerowaniu:**
- Zmodyfikować `LicenseController::store()` aby po przekierowaniu na `/admin/licenses` odczytywał `$_SESSION['_generated_licenses']` i renderował blok z kluczami.
- Wyświetlić tabelę z wygenerowanymi kluczami z przyciskiem „Kopiuj" (JavaScript `navigator.clipboard`).
- Po wyświetleniu — wyczyścić sesję.

**W widoku show.php:**
- Dodać adnotację „Pełny klucz nie jest przechowywany w bazie — dostępny tylko przy generowaniu".
- Opcjonalnie: pole do wyszukiwania po fragmencie klucza.

### Pliki do zmiany
- `src/Controllers/Admin/LicenseController.php` — odczyt sesji i przekazanie do widoku
- `resources/views/admin/licenses/index.php` — blok z kluczami po wygenerowaniu
- `resources/views/admin/licenses/show.php` — adnotacja przy polu klucz

---

## 2. Opisy pól — tooltips i help text

### Problem
Żadne pole w systemie nie ma opisu — użytkownik nie wie co oznaczają opcje takie jak `grace period`, `updates_expires_at`, `support_active` itp.

### Rozwiązanie
Dodać pod każdym polem `<small class="form-text text-muted">` z wyjaśnieniem.

| Pole | Opis |
|------|------|
| `expires_at` | Data wygaśnięcia licencji. Po tej dacie licencja otrzyma status `expired`. |
| `is_lifetime` | Licencja nigdy nie wygasa — ignoruje `expires_at`. |
| `updates_expires_at` | Data do której dostępne są aktualizacje produktu. Po tej dacie pole `updates_allowed` jest ignorowane. |
| `support_expires_at` | Data do której aktywne jest wsparcie. |
| `updates_allowed` | Czy ta licencja może pobierać aktualizacje produktu przez API. |
| `support_active` | Czy wsparcie jest aktywne dla tej licencji. |
| `activation_limit` | Maksymalna liczba aktywnych domen. Wartość `0` = bez limitu. |
| `grace_period_days` | Globalne ustawienie (w Settings) — liczba dni po wygaśnięciu, podczas których licencja nadal działa. |
| `plan_name` | Nazwa planu przypisanego do licencji — wybierana z listy planów zdefiniowanych w produkcie. |
| `allowed_channels` | Jakie kanały dystrybucji może używać ta licencja (stable, beta, lub oba). |

### Pliki do zmiany
- `resources/views/admin/licenses/form.php`
- `resources/views/admin/licenses/show.php`
- `resources/views/admin/products/form.php`
- `resources/views/admin/settings/*.php`

---

## 3. Przełączanie kanału dystrybucji przez użytkownika

### Kontekst
Użytkownik (właściciel licencji) chce móc sam we wtyczce WordPress przełączyć się między kanałem `stable` a `beta` **bez kontaktu z adminem**. Licencja pozostaje ta sama — zmienia się tylko to, jakie wersje są pobierane.

### Architektura

**Zasada:** serwer jest **stateless** w kwestii wyboru kanału — preferencja siedzi w wtyczce, serwer egzekwuje tylko uprawnienia.

```
Wtyczka → [channel=beta] → API /check lub /update → serwer waliduje → zwraca wersję z żądanego kanału
```

**Model uprawnień (per-licencja):**

| `allowed_channels` | Efekt |
|--------------------|-------|
| `stable,beta` (domyślnie) | Użytkownik może przełączać sam |
| `stable` | Tylko stable — zablokowane przez admina (tańszy plan) |
| `beta` | Tylko beta (rzadkie) |

### Zmiany w bazie danych

**Migracja:** `20260810_000005_add_channel_to_licenses.php`

```sql
ALTER TABLE licenses ADD COLUMN allowed_channels VARCHAR(40) NOT NULL DEFAULT 'stable,beta';
```

### Zmiany w API

Endpoint `/api/v1/{slug}/check`, `/api/v1/{slug}/update`, `/api/v1/{slug}/activate`:
- Akceptować parametr `channel` (opcjonalny, domyślnie `stable`)
- Walidować: czy `channel` należy do `allowed_channels` licencji
- Jeśli nie → odpowiedź `403` z `error: channel_not_allowed`
- Odpowiedź zawiera pole `"channel": "beta"` z aktualnie używanym kanałem

### Zmiany w panelu admina

W formularzu tworzenia i edycji licencji:
- Nowe pole `allowed_channels` — lista checkboxów: `☑ stable  ☑ beta`
- Opis: „Które kanały może używać posiadacz tej licencji (przełączane przez niego samodzielnie w ustawieniach wtyczki)"

### Zmiany w wtyczce WordPress

W `docs/wordpress-integration.md` i `docs/wordpress-license-client.php`:
- Dodać obsługę opcji `myplugin_update_channel` (zapisywanej przez wtyczkę)
- Przy każdym żądaniu API dołączać `channel` z tej opcji
- W ustawieniach wtyczki → nowy dropdown: „Kanał aktualizacji: Stabilny / Beta"

### Pliki do zmiany
- `database/migrations/20260810_000005_add_channel_to_licenses.php` — **NOWY**
- `src/Services/LicenseService.php` — walidacja `allowed_channels`
- `src/Services/UpdateService.php` — uwzględnienie `channel` z requesta
- `src/Controllers/Api/` — przekazanie `channel` do serwisów
- `src/Controllers/Admin/LicenseController.php` — obsługa `allowed_channels`
- `resources/views/admin/licenses/form.php` — pole `allowed_channels`
- `resources/views/admin/licenses/show.php` — pole `allowed_channels`
- `docs/wordpress-license-client.php` — obsługa kanału
- `docs/wordpress-integration.md` — dokumentacja

---

## 4. Plany licencji z listy konfigurowanej w produkcie

### Problem
`plan_name` to wolny tekst bez walidacji — można wpisać dowolną wartość niezwiązaną z produktem. Brakuje centralnej definicji planów per-produkt.

### Rozwiązanie

**Migracja:** `20260810_000006_add_plans_to_products.php`

```sql
ALTER TABLE products ADD COLUMN plans TEXT NULL; -- JSON array: ["starter","pro","enterprise"]
```

### Zmiany w panelu admina

**Formularz produktu (`products/form.php`):**
- Nowe pole „Dostępne plany" — input z tagami (lub textarea z wartościami oddzielonymi przecinkami, np. `starter,pro,enterprise`)
- Opis: „Lista planów dostępnych dla tego produktu. Przy tworzeniu licencji plan będzie wybierany z tej listy."

**Formularz tworzenia licencji (`licenses/form.php`):**
- Pole `plan_name` zmienić z `<input type="text">` na `<select>`
- Select ładowany dynamicznie (JavaScript) po zmianie produktu
- Plany osadzone jako JSON w atrybucie `data-plans` na każdej opcji `<option>` produktu
- Fallback: jeśli produkt nie ma zdefiniowanych planów — pokazać wolne pole tekstowe

### Walidacja server-side

W `LicenseController::store()`:
- Pobrać plany produktu
- Jeśli produkt ma zdefiniowane plany → walidować że `plan_name` należy do listy
- Jeśli brak planów w produkcie → akceptować dowolny string (backward-compat)

### Pliki do zmiany
- `database/migrations/20260810_000006_add_plans_to_products.php` — **NOWY**
- `src/Services/ProductService.php` — save/read `plans` JSON
- `src/Controllers/Admin/LicenseController.php` — walidacja `plan_name`
- `resources/views/admin/products/form.php` — pole planów
- `resources/views/admin/licenses/form.php` — dynamiczny select planów

---

## 5. Lifetime — blokowanie pól przy zaznaczeniu

### Problem (błędy logiczne)
1. Zaznaczenie `is_lifetime` nie blokuje pól `expires_at`, `updates_expires_at`, `support_expires_at` — wartości są zapisywane i powodują niespójność w UI (daty wyglądają jakby obowiązywały).
2. Odznaczenie `updates_allowed` nie blokuje `updates_expires_at`.
3. Odznaczenie `support_active` nie blokuje `support_expires_at`.
4. W `LicenseService::updatesAllowed()` — jeśli `is_lifetime=1` i `updates_expires_at` jest ustawiona, aktualizacje mogą być odcięte mimo lifetime.

### Rozwiązanie

**JavaScript (frontend):**

```javascript
// Kiedy is_lifetime zaznaczone:
// → wyczyść i wyłącz: expires_at, updates_expires_at, support_expires_at
// → wymuś zaznaczenie: updates_allowed, support_active

// Kiedy updates_allowed odznaczone:
// → wyczyść i wyłącz: updates_expires_at

// Kiedy support_active odznaczone:
// → wyczyść i wyłącz: support_expires_at
```

**PHP server-side (obrona przed obejściem JS):**

W `LicenseService::generateLicenses()` i `LicenseController::updateStatus()`:
```php
if ($data['is_lifetime']) {
    $data['expires_at'] = null;
    $data['updates_expires_at'] = null;
    $data['support_expires_at'] = null;
}
if (!$data['updates_allowed']) {
    $data['updates_expires_at'] = null;
}
if (!$data['support_active']) {
    $data['support_expires_at'] = null;
}
```

**W `LicenseService::updatesAllowed()`:**
```php
// Jeśli is_lifetime → ignoruj updates_expires_at
if ((int) $license['is_lifetime'] === 1) {
    return !in_array($this->statusForLicense($license), ['revoked', 'suspended'], true);
}
```

### Pliki do zmiany
- `resources/views/admin/licenses/form.php` — JavaScript
- `resources/views/admin/licenses/show.php` — JavaScript + dodać `is_lifetime` checkbox
- `src/Services/LicenseService.php` — server-side sanitacja + `updatesAllowed()`
- `src/Controllers/Admin/LicenseController.php` — `updateStatus()` — uwzględnić `is_lifetime`

---

## 6. Redesign UI — nowoczesny wygląd blokowy

### Problem
Obecny UI: płaski, brak hierarchii wizualnej, pola formularzy bez opisów, tabele bez kolorowania, sidebar brak.

### Rozwiązanie

**Layout (`layouts/app.php`):**
- Zamienić top navbar na **sidebar** (lewy, stały na desktopie, collapsed na mobile)
- Dodać ikony — Bootstrap Icons CDN (`https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css`)
- Dark sidebar (`#1e2235`) + białe tło treści
- Breadcrumbs w headerze strony

**Globalne style (`public/assets/app.css`):**
- CSS custom properties: `--color-primary`, `--color-sidebar`, `--radius-card`
- Card radius: `12px`, shadow: `0 1px 4px rgba(0,0,0,.08)`
- Badge statusów: kolorowe pill badges (active=green, expired=red, suspended=yellow, inactive=grey)

**Dashboard:**
- 4 bloki KPI: Aktywne licencje / Aktywacje dziś / Produkty / Błędy API ostatnie 24h
- Tabela ostatnich 10 licencji

**Lista licencji (`licenses/index.php`):**
- Tabela z pill badges na statusach
- Filtry w karcie powyżej tabeli (inline, nie osobna strona)
- Przycisk „Eksport CSV" w prawym górnym rogu

**Formularz licencji (`licenses/form.php`):**
- Sekcje grupowane kartami: „Podstawowe", „Terminy i ważność", „Opcje"
- Każde pole z `<small>` opisem
- Blok podglądu wygenerowanych kluczy po wygenerowaniu (punkt 1)

**Widok licencji (`licenses/show.php`):**
- Lewa kolumna: dane + formularz edycji
- Prawa kolumna: aktywacje (timeline), historia zmian (audit log)
- Blok „Klucz licencji" z adnotacją o maskowanym kluczu

**Formularz produktu (`products/form.php`):**
- Tabs: „Ogólne", „Plany", „Wersje"
- Tabela wersji z badge'ami kanału i statusu

**Formularze logowania (`auth/`):**
- Wycentrowana karta logowania, nowoczesny styl

### Pliki do zmiany
- `resources/views/layouts/app.php` — sidebar layout
- `public/assets/app.css` — nowy design system
- `resources/views/admin/dashboard.php`
- `resources/views/admin/licenses/index.php`
- `resources/views/admin/licenses/form.php`
- `resources/views/admin/licenses/show.php`
- `resources/views/admin/products/form.php`
- `resources/views/admin/products/index.php`
- `resources/views/auth/*.php`

---

## 7. Naprawy błędów logicznych

### B — `updatesAllowed()` ignoruje `is_lifetime` dla dat
**Priorytet: WYSOKI**
- Lokalizacja: `src/Services/LicenseService.php::updatesAllowed()`
- Problem: lifetime licencja z ustawioną datą `updates_expires_at` straci aktualizacje po tej dacie mimo `is_lifetime=1`
- Naprawa: early return jeśli `is_lifetime`

### F — NULL safety dla dat w `show.php`
**Priorytet: WYSOKI**
- Lokalizacja: `resources/views/admin/licenses/show.php` linia 4
- Problem: `substr(null, 0, 16)` → PHP 8.x deprecation/TypeError
- Naprawa: `!empty($license['expires_at']) ? str_replace(...) : ''`

### G — Brak `is_lifetime` w formularzu edycji
**Priorytet: WYSOKI**
- Lokalizacja: `resources/views/admin/licenses/show.php` + `LicenseController::updateStatus()`
- Problem: `is_lifetime` nie można zmienić po utworzeniu licencji, a controller nie aktualizuje tego pola
- Naprawa: dodać checkbox `is_lifetime` do formularza edycji + obsłużyć w controllerze

### A — Wygenerowane klucze nie są wyświetlane
**Priorytet: WYSOKI** — patrz punkt 1

### C — `activations_in_use` niezspójne przy soft-delete aktywacji
**Priorytet: ŚREDNI**
- Lokalizacja: `src/Services/LicenseService.php`
- Problem: soft-delete aktywacji (`deleted_at`) nie przelicza `activations_in_use`
- Naprawa: przy każdej operacji `deleteActivation` wywołać `activeActivationsCount()` i zaktualizować licencję

### D — Brak paginacji w `searchLicenses()`
**Priorytet: ŚREDNI**
- Lokalizacja: `src/Services/LicenseService.php::searchLicenses()`
- Problem: zwraca wszystkie wyniki bez limitu
- Naprawa: dodać `LIMIT :limit OFFSET :offset` z domyślnym limitem 200

### E — Podwójny named param w PDO (SQLite)
**Priorytet: ŚREDNI**
- Lokalizacja: `src/Services/ProductService.php::latestVersionForChannel()`
- Problem: `:requested_channel` użyty dwa razy w WHERE — PDO SQLite może nie obsługiwać poprawnie
- Naprawa: przepisać warunek na subquery lub użyć `CASE WHEN`

### H — Brak limitu w eksporcie CSV
**Priorytet: NISKI**
- Lokalizacja: `src/Controllers/Admin/LicenseController.php::exportCsv()`
- Naprawa: dodać limit lub streaming z fpassthru

### I — Brak walidacji `plan_name`
**Priorytet: NISKI** — rozwiązany przez punkt 4

### J — Logika DB bezpośrednio w kontrolerze
**Priorytet: NISKI** — refactoring
- Lokalizacja: `LicenseController::show()` — używa `$this->app->db()` bezpośrednio
- Naprawa: przenieść do `LicenseService`

---

## Kolejność implementacji (priorytety)

```
Faza 1 — Błędy krytyczne (szybkie naprawy)
  ├── B: updatesAllowed() lifetime fix
  ├── F: NULL safety dla dat
  └── G: is_lifetime w formularzu edycji

Faza 2 — Kluczowe funkcje
  ├── 1: Wyświetlanie kluczy po wygenerowaniu
  ├── 5: Lifetime blokowanie pól (JS + server-side)
  └── 3: Kanał dystrybucji (migracja + API + UI)

Faza 3 — Nowe funkcje
  ├── 4: Plany z listy produktu (migracja + UI)
  └── 2: Opisy pól

Faza 4 — UI i jakość
  ├── 6: Redesign UI
  ├── C/D/E: Poprawki serwisów
  └── H/J: Refactoring
```

---

## Wymagania techniczne

- PHP 8.2+
- SQLite (development) / MySQL 8+ (production)
- Bootstrap 5.3.x
- Bootstrap Icons 1.11.x
- Brak zewnętrznych JS frameworków (vanilla JS)
- Wszystkie migracje w `database/migrations/` z timestampem
