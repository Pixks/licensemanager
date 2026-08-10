# LicenseManager

Lekka, produkcyjna aplikacja PHP dla hostingu współdzielonego, pełniąca rolę serwera licencji, serwera aktualizacji prywatnych wtyczek WordPress, panelu administracyjnego i REST API.

## 1. Wybór stacku i uzasadnienie
- **PHP 8.2+ + PDO + Composer autoload**: działa na zwykłym hostingu WWW bez procesów stale działających w tle.
- **Własny lekki MVC**: Laravel był preferowany, ale w tym środowisku nie udało się pobrać jego szkieletu z GitHub bez autoryzacji; dlatego projekt używa własnego stosu zgodnego z Composerem i shared hostingiem.
- **MySQL / MariaDB**: główna baza produkcyjna.
- **Plikowy cache/rate limit**: brak zależności od Redisa.
- **Cron CLI lub HTTP**: utrzymanie systemu bez workerów.

## 2. Architektura systemu
- `public/` — front controller i statyczne assety.
- `src/Controllers/Admin` — panel administracyjny.
- `src/Controllers/Api` — REST API `/api/v1`.
- `src/Services` — logika domenowa: licencje, aktualizacje, tokeny, domeny, upload, rate limit.
- `src/Database` + `database/migrations` — migracje i połączenie PDO.
- `resources/views` — klasyczne, responsywne widoki HTML.
- `storage/app/private/packages` — prywatne ZIP-y wersji.
- `docs/` — API, wdrożenie i moduł WordPress.

## 3. Struktura katalogów
```text
bin/
bootstrap/
config/
database/
  migrations/
  seeders/
docs/
public/
resources/views/
src/
  Auth/
  Controllers/
  Database/
  Http/
  Models/
  Services/
  Validation/
  View/
storage/
tests/
```

## 4. Schemat relacji bazy danych
- `users` ⇄ `roles` przez `role_user`
- `roles` ⇄ `permissions` przez `permission_role`
- `products` 1..n `product_versions`
- `products` 1..n `licenses`
- `licenses` 1..n `license_activations`
- `licenses` 1..n `license_domain_rules`
- `licenses` 1..n `download_tokens`
- `download_tokens` 1..n `download_logs`

## 5. Kontrakt REST API
Pełna dokumentacja: [`docs/api.md`](docs/api.md)

## 6. Zasady bezpieczeństwa
- hash haseł przez `password_hash()`
- role + uprawnienia
- CSRF dla panelu
- PDO prepared statements
- escape HTML przez `htmlspecialchars()`
- rate limiting plikowy dla logowania i API
- logowanie zdarzeń bezpieczeństwa
- wymuszenie HTTPS
- hashowane klucze licencyjne i tokeny pobrań
- maskowanie kluczy w logach
- walidacja uploadu ZIP (rozmiar, MIME, rozszerzenie)

## 7. Strategia aktualizacji WordPress
- wtyczka aktywuje licencję na canonical domain
- okresowy `heartbeat` lub `check` przez `wp_cron`
- pozytywny status cachowany lokalnie przez grace period
- `updates/check` zwraca tylko ważne aktualizacje dla poprawnej licencji
- `updates/download` używa krótkotrwałego tokenu
- ZIP nie jest ujawniany bezpośrednio publicznym URL-em

## Uruchomienie lokalne
```bash
cp .env.example .env
composer dump-autoload
php bin/console migrate
php bin/console seed:roles
php bin/console create-admin
php -S 127.0.0.1:8080 -t public
```

## Wdrożenie „wrzucasz i działa”
```bash
composer install:shared-hosting
composer check:shared-hosting
composer smoke:shared-hosting
```

- pełna instrukcja: [`docs/deployment.md`](docs/deployment.md)
- paczka ZIP do wrzucenia na serwer: `composer build:zip`

## Testy
```bash
composer test
```
