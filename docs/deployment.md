# Wdrożenie na hostingu współdzielonym

## Szybka checklista („jak WordPress”)
1. Wgraj projekt na hosting.
2. Ustaw domenę tak, aby document root wskazywał na katalog `public/`.
3. Uruchom instalator:
   ```bash
   composer install:shared-hosting
   ```
4. Uruchom walidację środowiska:
   ```bash
   composer check:shared-hosting
   ```
5. Dodaj cron (CLI albo HTTP).
6. Uruchom szybki smoke test:
   ```bash
   composer smoke:shared-hosting
   ```

## Co robi `setup:install`
- tworzy `.env` z `.env.example` (jeśli brak),
- pyta tylko o podstawowe dane (`APP_URL`, DB, admin),
- generuje `APP_KEY` i `CRON_SECRET` jeśli są placeholderami,
- uruchamia migracje, seed ról i tworzy konto admina.

## Wariant dla hostingu z wymuszonym `public_html`
Jeśli nie możesz wskazać document root bezpośrednio na `public/`:
1. Trzymaj cały projekt poza webrootem (np. `~/licensemanager`).
2. Skopiuj **zawartość** katalogu `public/` do `public_html/`.
3. W `public_html/index.php` zmień ścieżki tak, aby wskazywały na katalog projektu, np.:
   ```php
   require_once __DIR__ . '/../licensemanager/bootstrap/functions.php';
   $app = require base_path('bootstrap/app.php');
   ```
4. Skopiuj `public/.htaccess` do `public_html/.htaccess`.

### Cron CLI
```bash
0 */6 * * * /usr/bin/php /home/USER/domains/example.com/licensemanager/bin/console cron:run >> /home/USER/cron-licensemanager.log 2>&1
```

### Cron HTTP
```bash
0 */6 * * * /usr/bin/curl -fsS "https://licenses.example.com/cron/run?secret=YOUR_CRON_SECRET" >/dev/null
```

## Walidacja domeny i HTTPS
Komenda:
```bash
composer check:shared-hosting
```
sprawdza:
- poprawność `APP_URL`,
- zgodność `APP_FORCE_HTTPS=true` z adresem `https://...`,
- obecność i jakość `CRON_SECRET`,
- połączenie z bazą,
- zapisywalność `storage/cache`, `storage/logs`, `storage/app/private`.

## Szybki test powdrożeniowy
```bash
composer smoke:shared-hosting
```
Domyślnie test bierze URL z `APP_URL` i sprawdza odpowiedź panelu oraz API.

## Paczka production-ready (ZIP)
```bash
composer build:zip
```
Gotowa paczka pojawi się w `storage/releases/`.
