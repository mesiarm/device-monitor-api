# Device Monitor API

Jednoduché REST API na správu zariadení a ich pingov (stav batérie).
Laravel 13, PHP 8.5, MySQL 8.4. Bez frontendu a bez autentifikácie.

## Spustenie cez Docker

Potrebné je mať nainštalovaný Docker s pluginom Docker Compose.

```bash
git clone https://github.com/mesiarm/device-monitor-api.git
cd device-monitor-api
```

1. Nastavenie `.env`:

   ```bash
   cp .env.example .env
   ```

   Predvolené hodnoty v `.env.example` sú nastavené pre Docker (databáza `db`, používateľ `laravel`), netreba ich meniť.

2. Zostavenie obrazu a inštalácia závislostí:

   ```bash
   docker compose build
   docker compose run --rm app composer install
   ```

3. Vygenerovanie kľúča aplikácie:

   ```bash
   docker compose run --rm app php artisan key:generate
   ```

4. Spustenie aplikácie a databázy:

   ```bash
   docker compose up -d
   ```

5. Migrácie:

   ```bash
   docker compose exec app php artisan migrate
   ```

6. Testy:

   ```bash
   docker compose exec app php artisan test
   ```

   Testy bežia na SQLite v pamäti, vývojovú databázu nijako nemenia.

API je dostupné na `http://localhost:8000/api`. Kontrola, že aplikácia beží: `http://localhost:8000/up`.

Zastavenie: `docker compose down` (s prepínačom `-v` sa zmaže aj databáza).

## Spustenie lokálne (bez Dockeru)

Vyžaduje PHP 8.3 alebo novší (s rozšírením `pdo_mysql` alebo `pdo_sqlite`), Composer a databázu.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

V `.env` nastav pripojenie k databáze, napríklad pre MySQL `DB_HOST=127.0.0.1` a vlastné údaje, alebo pre SQLite `DB_CONNECTION=sqlite` (a vymaž ostatné `DB_*` hodnoty). Potom:

```bash
php artisan migrate
php artisan test
php artisan serve
```

## Endpointy

| Metóda | Cesta | Popis | Úspešný kód |
|---|---|---|---|
| POST | `/api/devices` | Vytvorenie zariadenia | 201 |
| POST | `/api/devices/{uuid}/ping` | Uloženie pingu zariadenia | 201 |
| GET | `/api/devices/{uuid}` | Detail zariadenia | 200 |
| GET | `/api/devices` | Zoznam zariadení | 200 |

Zariadenie sa v URL identifikuje pomocou `uuid`, nie číselného `id`.
Chyby: `422` pri neplatných dátach (telo obsahuje `message` a `errors`), `404` pre neznáme zariadenie, `405` pre nepodporovanú metódu.
Pri volaní odporúčam posielať hlavičku `Accept: application/json`.

### Vytvorenie zariadenia

```bash
curl -X POST http://localhost:8000/api/devices \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"uuid": "550e8400-e29b-41d4-a716-446655440000", "name": "Sensor 01"}'
```

```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "name": "Sensor 01"
}
```

Validácia: `uuid` je povinné, musí byť platné UUID a unikátne. `name` je povinný reťazec, najviac 255 znakov.

### Uloženie pingu

```bash
curl -X POST http://localhost:8000/api/devices/550e8400-e29b-41d4-a716-446655440000/ping \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"battery_percent": 85}'
```

```json
{
  "status": "ok"
}
```

Validácia: `battery_percent` je povinné celé číslo od 0 do 100 (číslo v JSON, nie text).

### Detail zariadenia

```bash
curl -H 'Accept: application/json' http://localhost:8000/api/devices/550e8400-e29b-41d4-a716-446655440000
```

```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "name": "Sensor 01",
  "last_ping": "2026-05-26T10:00:00Z",
  "last_battery_percent": 85
}
```

Ak zariadenie ešte nemá žiadny ping, `last_ping` aj `last_battery_percent` sú `null`.

### Zoznam zariadení

```bash
curl -H 'Accept: application/json' http://localhost:8000/api/devices
```

```json
[
  {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Sensor 01",
    "last_battery_percent": 85
  }
]
```

Zariadenia sú zoradené podľa vytvorenia. Posledný ping sa načítava hromadne, takže počet dotazov nezávisí od počtu zariadení.

## Dokumentácia API

Dokumentácia sa generuje automaticky z kódu (routy, FormRequesty a API Resources) pomocou [Scramble](https://scramble.dedoc.co/).

- Interaktívna dokumentácia (s možnosťou vyskúšať požiadavky): <http://localhost:8000/docs/api>. Je dostupná len v prostredí `APP_ENV=local`.
- Špecifikácia OpenAPI: [`docs/openapi.json`](docs/openapi.json). Dá sa importovať napríklad do Postmana. Po zmene API ju treba znova vygenerovať:

  ```bash
  docker compose exec app php artisan scramble:export
  ```

  Test `DocumentationTest` zlyhá, ak je súbor neaktuálny.
- [`docs/requests.http`](docs/requests.http): ukážkové požiadavky, ktoré sa dajú spustiť priamo v PhpStorme.

## Štruktúra

| Časť | Umiestnenie |
|---|---|
| Routy | `routes/api.php` |
| Controller | `app/Http/Controllers/DeviceController.php` |
| FormRequest validácie | `app/Http/Requests/` |
| API Resource triedy | `app/Http/Resources/` |
| Modely | `app/Models/Device.php`, `app/Models/Ping.php` |
| Migrácie a factories | `database/migrations/`, `database/factories/` |
| Testy | `tests/Feature/Api/`, `tests/Unit/` |
| Dokumentácia API | `docs/` |

## Poznámky

- Posledný ping sa určuje podľa `created_at`, pri zhode podľa `id`.
- Tabuľka `pings` má podľa zadania iba `created_at`, preto model `Ping` nepoužíva `updated_at`.
- V `.env.example` je `APP_DEBUG=true` kvôli lokálnemu vývoju. Pri nasadení ho treba nastaviť na `false`.
