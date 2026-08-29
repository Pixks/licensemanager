# Instrukcja aktualizacji systemu — dla właściciela

> Dokument dla: **administratora / właściciela systemu**  
> Repozytorium: `Pixks/licensemanager`

---

## Co zostanie zmienione w systemie

W skrócie planowane aktualizacje podzielone są na 4 fazy:

| Faza | Co się zmienia |
|------|---------------|
| 1 | Naprawy błędów logicznych (pilne) |
| 2 | Nowe funkcje: kanał beta/stable, plany produktu, podgląd kluczy |
| 3 | Opisy pól w formularzach |
| 4 | Nowy wygląd (sidebar, karty, ikony) |

**Szczegółowy opis zmian:** patrz `docs/UPDATE_PLAN.md`

---

## Przed aktualizacją — lista kontrolna

Wykonaj te kroki **zanim** ktokolwiek wgra zmiany na serwer:

- [ ] **Zrób kopię zapasową bazy danych**
  - SQLite: skopiuj plik `.db` (np. `storage/database.sqlite`) w bezpieczne miejsce
  - MySQL: `mysqldump -u USER -p DBNAME > backup_$(date +%Y%m%d).sql`
- [ ] **Zrób kopię katalogu z plikami produktów** (np. `storage/uploads/` lub podobny)
- [ ] **Zanotuj aktualną wersję** commitów (`git log --oneline -5`)
- [ ] **Poinformuj użytkowników** jeśli system będzie chwilowo niedostępny

---

## Procedura aktualizacji na hostingu współdzielonym

### Krok 1 — Wgraj nowe pliki

**Opcja A: przez Git (zalecane jeśli masz SSH na hostingu)**
```bash
cd ~/licensemanager   # katalog projektu na serwerze
git pull origin main
```

**Opcja B: przez FTP/SFTP**
1. Pobierz nowe pliki z repozytorium (ZIP lub przez klienta Git)
2. Wgraj przez FileZilla lub podobny klient
3. Nadpisz istniejące pliki
4. **NIE usuwaj** katalogu `storage/` i pliku `.env`

---

### Krok 2 — Zaktualizuj zależności PHP

Przez SSH:
```bash
cd ~/licensemanager
composer install --no-dev --optimize-autoloader
```

Jeśli nie masz dostępu SSH z Composerem — pomiń ten krok (zależności zmieniają się rzadko; sprawdź czy `composer.json` się zmienił).

---

### Krok 3 — Uruchom migracje bazy danych

Przez SSH:
```bash
php bin/console migrate
```

Lub jeśli masz panel hostingowy z dostępem do PHP CLI — uruchom komendę z katalogu projektu.

**Co robią migracje w tej aktualizacji:**
- Dodają kolumnę `allowed_channels` do tabeli `licenses` (domyślna wartość: `stable,beta` — **wszystkie istniejące licencje automatycznie dostaną tę wartość**)
- Dodają kolumnę `plans` do tabeli `products` (domyślna wartość: pusta — bez zmian dla istniejących produktów)

**Migracje są bezpieczne** — nie kasują żadnych danych, tylko dodają nowe kolumny.

---

### Krok 4 — Wyczyść cache (jeśli dotyczy)

Jeśli korzystasz z cache PHP (OPcache):
```bash
# Przez CLI
php -r "opcache_reset();"

# Lub przez panel hostingowy: Restartuj PHP
```

---

### Krok 5 — Sprawdź czy system działa

1. Wejdź na panel admina: `https://twojastrona.pl/admin`
2. Zaloguj się
3. Sprawdź:
   - [ ] Lista licencji się ładuje
   - [ ] Lista produktów się ładuje
   - [ ] Formularz nowej licencji działa
   - [ ] API działa: `https://twojastrona.pl/api/v1/SLUG/check` (przez wtyczkę)

---

## Co się zmienia dla Ciebie jako admina — nowe funkcje

### Po Fazie 1 (naprawy)
- **Lifetimowe licencje** działają teraz poprawnie — daty `updates_expires_at` i `support_expires_at` nie blokują aktualizacji jeśli licencja jest lifetime
- **Formularz edycji licencji** ma teraz pole `is_lifetime` — można je zmienić po utworzeniu licencji
- **Daty NULL** nie generują już błędów PHP

### Po Fazie 2 (nowe funkcje)

**Klucze licencji po wygenerowaniu:**
- Po kliknięciu „Generuj licencje" system wyświetli tabelę z **pełnymi kluczami** z przyciskiem kopiowania
- **Ważne:** to jedyna chwila kiedy możesz zobaczyć pełny klucz — po zamknięciu strony klucz jest już tylko zamaskowany w bazie
- Zapisz/wyślij klucze do klienta od razu po wygenerowaniu

**Kanał dystrybucji (stable/beta):**
- W każdej licencji pojawi się nowe pole „Dozwolone kanały": `☑ Stable  ☑ Beta`
- Domyślnie: oba kanały zaznaczone — klient może sam wybrać kanał w ustawieniach wtyczki
- Jeśli chcesz zablokować klienta tylko do wersji stabilnych — odznacz `Beta`
- Klient przełącza kanał SAM w ustawieniach swojej wtyczki — Ty tylko decydujesz co jest dozwolone

**Plany licencji:**
- W każdym produkcie możesz teraz zdefiniować listę planów (np. `starter,pro,enterprise`)
- Przy tworzeniu licencji — zamiast wolnego pola tekstowego będzie lista do wyboru
- Istniejące licencje nie są zmieniane

### Po Fazie 4 (nowy wygląd)
- Panel ma sidebar po lewej stronie zamiast paska na górze
- Karty, ikony, kolorowe statusy
- Wszystkie dane i funkcje pozostają takie same — zmienia się tylko wygląd

---

## Cofnięcie zmian (rollback)

Jeśli coś pójdzie nie tak:

**Pliki:**
```bash
git revert HEAD  # cofnij ostatni commit
# lub
git reset --hard POPRZEDNI_HASH_COMMITA
```

**Baza danych:**
- SQLite: nadpisz plik `.db` kopią zapasową
- MySQL: `mysql -u USER -p DBNAME < backup_YYYYMMDD.sql`

**Uwaga:** migracje bazy nie są odwracalne automatycznie. Nowe kolumny (`allowed_channels`, `plans`) są opcjonalne — system będzie działał nawet bez nich jeśli cofniesz tylko pliki PHP, ale wtedy po kliknięciu formularza mogą pojawić się błędy przy próbie zapisu tych pól. W takim razie najlepiej przywróć całą kopię bazy.

---

## Pytania i wsparcie

Jeśli masz pytania dotyczące aktualizacji:
1. Sprawdź `docs/UPDATE_PLAN.md` — pełny opis każdej zmiany
2. Sprawdź `docs/DEVELOPER_INSTRUCTIONS.md` — szczegóły techniczne
3. Sprawdź istniejące `docs/deployment.md` — ogólne wdrożenie

---

## Harmonogram (sugerowany)

| Kiedy | Co |
|-------|----|
| Jak najszybciej | Faza 1 — naprawy błędów (nie wymaga migracji) |
| Następny tydzień | Faza 2 — nowe funkcje (wymaga migracji bazy) |
| Dowolnie | Faza 3 — opisy pól (bez migracji) |
| Dowolnie | Faza 4 — nowy wygląd (bez migracji) |
