# START - Workshop Manager

Questa e' la guida principale per installare e avviare il progetto.

## Indice

- [Stack tecnico](#stack-tecnico)
- [Requisiti locali](#requisiti-locali)
- [Installazione passo-passo](#installazione-passo-passo)
- [Avvio in sviluppo](#avvio-in-sviluppo)
- [Verifica qualità e test](#verifica-qualita-e-test)
- [Troubleshooting rapido](#troubleshooting-rapido)

## Stack tecnico

- Backend: Laravel 13
- Auth backend: Laravel Fortify
- Frontend bridge: Inertia.js v3
- Frontend UI: Vue 3 + TypeScript
- Styling: Tailwind CSS v4
- Routing TS helper: Laravel Wayfinder
- Testing: Pest v4
- Build tool: Vite
- Queue default: database
- Session driver default: database
- Cache store default: database
- Mailer default: `log`
- Ambiente opzionale e consigliato per onboarding cross-platform: Laravel Sail con MySQL e Redis

## Requisiti locali

Per lavorare sul progetto in locale servono almeno:

- PHP 8.3+
- Composer 2.x
- Node.js 20+ con npm
- SQLite oppure MySQL
- estensioni PHP tipiche di Laravel, inclusi PDO, mbstring, openssl e tokenizer

Se vuoi usare Laravel Sail, aggiungi anche:

- Docker
- Docker Compose

In questo progetto Sail deve avviare almeno:

- MySQL
- Redis

Consiglio pratico:

- usare SQLite per partire rapidamente
- usare MySQL solo se serve testare un comportamento specifico del DB engine
- usare Sail quando vuoi un ambiente ripetibile su sistemi operativi diversi senza dipendere dalla configurazione locale del database

## Installazione passo-passo

### 1. Entrare nella root applicativa

```bash
cd workshop-manager
```

### 2. Installare le dipendenze backend

```bash
composer install
```

### 3. Installare le dipendenze frontend

```bash
npm install
```

### 4. Preparare il file ambiente

```bash
cp .env.example .env
```

### 5. Scegliere il metodo di esecuzione

#### Opzione consigliata: Laravel Sail

Laravel Sail e' il percorso migliore se vuoi un ambiente uniforme per macOS, Linux e Windows senza configurazioni locali complesse.

Questo progetto usa Sail con:

- MySQL
- Redis

Il repository include gia' il file `compose.yaml` con i servizi necessari.

Per partire:

```bash
docker compose up -d
```

Se preferisci il wrapper Sail standard:

```bash
./vendor/bin/sail up -d
```

Poi esegui le migration dentro il container:

```bash
./vendor/bin/sail artisan migrate
```

Se vuoi anche installare i dati demo o altri seed:

```bash
./vendor/bin/sail artisan db:seed
```

#### Opzione locale: database nativo

Usa una delle configurazioni seguenti:

##### SQLite

Aggiorna `.env` con una configurazione minima:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/workshop-manager/database/database.sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Poi crea il file SQLite:

```bash
touch database/database.sqlite
```

##### MySQL

Se vuoi usare MySQL, aggiorna `.env` con:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workshop_manager
DB_USERNAME=...
DB_PASSWORD=...
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### 6. Generare la chiave applicativa

```bash
php artisan key:generate
```

### 7. Eseguire le migration

```bash
php artisan migrate
```

### 8. Costruire gli asset oppure avviare il dev server

Build una tantum:

```bash
npm run build
```

Oppure sviluppo interattivo:

```bash
composer run dev
```

## Avvio in sviluppo

Il comando consigliato per lavorare localmente e':

```bash
composer run dev
```

Questo avvia:

- server Laravel
- queue listener
- Laravel Pail per i log
- Vite dev server

Se stai usando Sail, l'equivalente pratico e':

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan queue:listen --tries=1 --timeout=0
./vendor/bin/sail artisan pail --timeout=0
```

Se vuoi separare i processi:

```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1 --timeout=0
php artisan pail --timeout=0
```

## Verifica qualita e test

### Test applicativi

```bash
php artisan test --compact
```

Se stai usando Sail:

```bash
./vendor/bin/sail artisan test --compact
```

### Check frontend

```bash
npm run lint:check
npm run format:check
npm run types:check
```

### Check integrato

```bash
composer run test
composer run ci:check
```

## Troubleshooting rapido

### Errore Vite manifest mancante

Esegui:

```bash
npm run build
```

Oppure, in sviluppo:

```bash
npm run dev
```

### Login o pagine protette non funzionano dopo setup

Controlla:

- `.env`
- `APP_KEY`
- migrazioni eseguite
- tabella `sessions` presente

### Queue listener fermo

Le code usano il driver `database`, quindi la tabella `jobs` deve esistere e il listener deve essere avviato.

### Email non recapitate

Per default il mailer e' `log`, quindi le email finiscono nei log applicativi e non in una mailbox reale.

### Cache o sessioni rompono l'app dopo il setup

Con `CACHE_STORE=database` e `SESSION_DRIVER=database`, le migration devono essere eseguite correttamente prima di usare l'app.
