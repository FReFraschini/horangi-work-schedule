# Horangi Work Schedule

Applicazione web per la gestione dei turni di lavoro, costruita con **Laravel 12** e **Vue 3 + Vuetify 4**.

Supporta due ruoli utente: **gestore** (amministratore) e **operatore** (dipendente).

---

## Indice

- [Requisiti](#requisiti)
- [Setup rapido (sviluppo)](#setup-rapido-sviluppo)
- [Struttura del progetto](#struttura-del-progetto)
- [Ruoli e accessi](#ruoli-e-accessi)
- [Comandi](#comandi)
- [API — Rotte](#api--rotte)
- [Deploy in produzione](#deploy-in-produzione)
- [Credenziali di default](#credenziali-di-default)

---

## Requisiti

| Strumento | Versione minima |
|-----------|----------------|
| PHP       | 8.2            |
| Composer  | 2.x            |
| Node.js   | 18.x           |
| npm       | 9.x            |
| MySQL     | 8.0 *(oppure SQLite per sviluppo)* |
| Docker + Compose | v2 *(opzionale, per ambiente containerizzato)* |

---

## Setup rapido (sviluppo)

### Con Docker

```bash
# 1. Clona il repository
git clone https://github.com/FReFraschini/horangi-work-schedule.git
cd horangi-work-schedule

# 2. Copia e configura le variabili d'ambiente
cp .env.example .env          # credenziali Docker/MySQL
cp code/.env.example code/.env  # configurazione Laravel

# 3. Avvia i container
docker compose up -d --build

# 4. Prima installazione (dentro il container)
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install
docker compose exec app npm run build

# 5. Accedi su http://localhost:8080
```

### Senza Docker (locale)

Tutti i comandi vanno eseguiti dalla cartella `code/`:

```bash
cd code

# Installazione completa in un comando
composer setup

# Oppure passo per passo:
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build

# Avvia il server di sviluppo
composer dev        # avvia Laravel + Vite + queue + log in parallelo
```

---

## Struttura del progetto

```
horangi-work-schedule/
├── code/                          # Applicazione Laravel
│   ├── app/
│   │   ├── Console/Commands/      # Comandi Artisan custom
│   │   ├── Http/Controllers/
│   │   │   ├── Api/               # Controller API gestore
│   │   │   │   └── Operator/      # Controller API operatore
│   │   │   └── HomeController.php
│   │   └── Models/                # User, Shift, UnavailabilityRequest, Absence
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── resources/
│   │   ├── js/
│   │   │   ├── app.js             # Entry point Vue 3
│   │   │   └── pages/
│   │   │       ├── ScheduleDashboard.vue   # Vista gestore
│   │   │       └── OperatorDashboard.vue   # Vista operatore
│   │   └── views/
│   │       ├── layouts/app.blade.php
│   │       └── home.blade.php
│   └── routes/
│       ├── api.php
│       └── web.php
├── .docker/
│   ├── php/Dockerfile             # Multi-stage: base / dev / prod
│   ├── php/opcache.ini
│   ├── nginx/default.conf         # Config sviluppo
│   └── nginx/prod.conf            # Config produzione (HTTPS)
├── deploy/
│   ├── setup-oracle.sh            # Setup VM Oracle Cloud
│   ├── init-letsencrypt.sh        # Primo certificato SSL
│   └── env.prod.example           # Template .env produzione
├── docker-compose.yml             # Ambiente di sviluppo
├── docker-compose.prod.yml        # Ambiente di produzione
├── DEPLOY.md                      # Guida deploy completa
└── README.md
```

---

## Ruoli e accessi

### Gestore (`role: 'gestore'`)
- Accesso completo alla dashboard di pianificazione
- Gestione operatori (CRUD)
- Creazione e modifica turni
- Approvazione/rifiuto richieste di indisponibilità
- Gestione assenze (ferie, permessi, ecc.)
- Visualizzazione statistiche ore

### Operatore (`role: 'operatore'`)
- Visualizzazione dei propri turni
- Visualizzazione del calendario del team
- Invio richieste di indisponibilità
- Visualizzazione delle proprie assenze

---

## Comandi

### Composer (da `code/`)

| Comando | Descrizione |
|---------|-------------|
| `composer setup` | Installazione completa: deps, .env, key, migrate, build |
| `composer dev` | Avvia Laravel + Vite + queue + log in parallelo |
| `composer test` | Esegue la suite PHPUnit |

### Artisan (da `code/`)

| Comando | Descrizione |
|---------|-------------|
| `php artisan user:create` | Crea un utente in modo interattivo (scelta del ruolo) |
| `php artisan migrate` | Esegue le migration |
| `php artisan migrate --seed` | Migration + seeder (utenti di esempio) |
| `php artisan db:seed` | Solo seeder |
| `php artisan key:generate` | Genera APP_KEY |
| `php artisan config:cache` | Cache configurazione (produzione) |
| `php artisan route:cache` | Cache rotte (produzione) |
| `php artisan view:cache` | Cache viste (produzione) |
| `php artisan tinker` | REPL interattivo |

### npm (da `code/`)

| Comando | Descrizione |
|---------|-------------|
| `npm run dev` | Vite in modalità watch |
| `npm run build` | Build produzione con hash sui file |

---

## API — Rotte

Tutte le rotte API richiedono autenticazione via **Laravel Sanctum** (`auth:sanctum`).  
Il token CSRF viene letto automaticamente dal cookie `XSRF-TOKEN`.

### Autenticazione Web

| Metodo | Rotta | Descrizione |
|--------|-------|-------------|
| `GET`  | `/` | Redirect a `/home` |
| `GET`  | `/home` | Dashboard (gestore o operatore) |
| `GET`  | `/login` | Pagina di login |
| `POST` | `/login` | Effettua login |
| `POST` | `/logout` | Effettua logout |

---

### Rotte Gestore (`auth:sanctum` + gate `is-gestore`)

#### Utenti

| Metodo | Rotta | Descrizione |
|--------|-------|-------------|
| `GET`    | `/api/users` | Lista tutti gli operatori |
| `POST`   | `/api/users` | Crea un operatore |
| `GET`    | `/api/users/{id}` | Dettaglio operatore |
| `PUT`    | `/api/users/{id}` | Modifica operatore |
| `DELETE` | `/api/users/{id}` | Elimina operatore |

#### Turni

| Metodo | Rotta | Descrizione |
|--------|-------|-------------|
| `GET`    | `/api/shifts` | Lista turni (opz. `?start_date=&end_date=`) |
| `POST`   | `/api/shifts` | Crea turno |
| `GET`    | `/api/shifts/{id}` | Dettaglio turno |
| `PUT`    | `/api/shifts/{id}` | Modifica turno |
| `DELETE` | `/api/shifts/{id}` | Elimina turno |

#### Richieste di indisponibilità

| Metodo | Rotta | Descrizione |
|--------|-------|-------------|
| `GET`  | `/api/unavailability-requests` | Lista tutte le richieste |
| `GET`  | `/api/unavailability-requests/{id}` | Dettaglio richiesta |
| `PUT`  | `/api/unavailability-requests/{id}` | Modifica richiesta |
| `POST` | `/api/unavailability-requests/{id}/update-status` | Approva o rifiuta (`status: approvata\|rifiutata`) |

#### Assenze

| Metodo | Rotta | Descrizione |
|--------|-------|-------------|
| `GET`    | `/api/absences` | Lista assenze (opz. `?start_date=&end_date=`) |
| `POST`   | `/api/absences` | Registra assenza |
| `PUT`    | `/api/absences/{id}` | Modifica assenza |
| `DELETE` | `/api/absences/{id}` | Elimina assenza |

Tipi assenza validi: `ferie` · `permesso` · `compensativo` · `altra_assenza`

#### Dashboard

| Metodo | Rotta | Descrizione |
|--------|-------|-------------|
| `GET` | `/api/dashboard/totals?start_date=&end_date=` | Ore totali per operatore e per giorno |

---

### Rotte Operatore (`auth:sanctum`, accesso all'utente autenticato)

#### Turni personali

| Metodo | Rotta | Descrizione |
|--------|-------|-------------|
| `GET` | `/api/operator/shifts` | Turni dell'operatore loggato (opz. `?start_date=&end_date=`) |

#### Assenze personali

| Metodo | Rotta | Descrizione |
|--------|-------|-------------|
| `GET` | `/api/operator/absences` | Assenze dell'operatore (opz. `?date=`) |

#### Calendario del team

| Metodo | Rotta | Descrizione |
|--------|-------|-------------|
| `GET` | `/api/operator/schedule/shifts` | Tutti i turni del team (opz. `?start_date=&end_date=`) |
| `GET` | `/api/operator/schedule/absences` | Tutte le assenze del team (opz. `?start_date=&end_date=`) |
| `GET` | `/api/operator/schedule/team` | Lista operatori con nome, colore e ore settimanali |

#### Richieste di indisponibilità

| Metodo   | Rotta | Descrizione |
|----------|-------|-------------|
| `GET`    | `/api/operator/unavailability-requests` | Richieste personali (opz. `?archived=true`) |
| `POST`   | `/api/operator/unavailability-requests` | Invia nuova richiesta |
| `PATCH`  | `/api/operator/unavailability-requests/{id}/archive` | Archivia richiesta |
| `DELETE` | `/api/operator/unavailability-requests/{id}` | Elimina richiesta (solo `in attesa` o archiviate) |

Campi richiesta: `date` (unica per utente) · `preference: mattina\|pomeriggio\|tutto il giorno` · `note` (opzionale)

---

## Deploy in produzione

Vedi **[DEPLOY.md](./DEPLOY.md)** per la guida completa.

In sintesi: VM Oracle Cloud Always Free (ARM Ubuntu 22.04) + dominio gratuito DuckDNS + HTTPS automatico Let's Encrypt.

```bash
# Quick start sulla VM
git clone https://github.com/FReFraschini/horangi-work-schedule.git
cd horangi-work-schedule
bash deploy/setup-oracle.sh            # installa Docker, apre porte
# configura .env e code/.env ...
bash deploy/init-letsencrypt.sh        # ottiene certificato SSL
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --seed
docker compose -f docker-compose.prod.yml exec app php artisan user:create
```

---

## Credenziali di default

> Disponibili solo dopo `php artisan db:seed`. **Cambia la password dopo il primo accesso.**

| Ruolo | Email | Password |
|-------|-------|----------|
| Gestore | `gestore@example.com` | `password` |
| Operatore | `operatore@example.com` | `password` |

Per creare nuovi utenti:
```bash
php artisan user:create
```
