# Wdrożenie na hostingu współdzielonym

## Wybór stacku
Projekt używa lekkiego MVC opartego o PHP 8.2, PDO i Composer autoload. Laravel był preferowany, ale w tym środowisku nie udało się pobrać jego szkieletu z GitHub bez autoryzacji, więc wybrano własny stack w pełni zgodny z hostingiem współdzielonym.

## Kroki wdrożenia
1. Utwórz bazę MySQL/MariaDB i użytkownika.
2. Skopiuj `.env.example` do `.env` i uzupełnij sekrety.
3. Uruchom:
   ```bash
   composer dump-autoload
   php bin/console migrate
   php bin/console seed:roles
   php bin/console create-admin
   ```
4. Wgraj pliki na hosting.
5. Ustaw document root na katalog `public`.
6. Jeżeli hosting wymusza `public_html`, przenieś zawartość `public/` do `public_html/` i popraw ścieżki w `index.php`, aby wskazywały na katalog projektu poza webrootem.
7. Nadaj zapis dla `storage/cache`, `storage/logs`, `storage/app/private`.
8. Włącz HTTPS i pozostaw `APP_FORCE_HTTPS=true`.
9. Dodaj cron.

### Cron CLI
```bash
0 */6 * * * /usr/bin/php /home/USER/domains/example.com/licensemanager/bin/console cron:run >> /home/USER/cron-licensemanager.log 2>&1
```

### Cron HTTP
```bash
0 */6 * * * /usr/bin/curl -fsS "https://licenses.example.com/cron/run?secret=YOUR_CRON_SECRET" >/dev/null
```
