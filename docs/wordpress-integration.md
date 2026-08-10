# Integracja z WordPress

- Wtyczka powinna cache'ować ostatni poprawny wynik walidacji licencji przez 7-14 dni.
- Chwilowy brak dostępu do API nie powinien blokować funkcji premium.
- Po potwierdzonym `license_expired`, `license_revoked` lub `license_suspended` blokuj aktualizacje i funkcje premium zależne od aktywnej subskrypcji, ale nie usuwaj danych użytkownika.
- Przykładowa klasa klienta znajduje się w `docs/wordpress-license-client.php`.
