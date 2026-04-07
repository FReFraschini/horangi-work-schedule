# Guida al Deploy — Oracle Cloud Free + horangi.duckdns.org

Guida completa per pubblicare l'app su una VM Oracle Cloud Always Free con HTTPS automatico tramite Let's Encrypt.

---

## Prerequisiti

| Requisito | Note |
|-----------|------|
| Account Oracle Cloud | Gratuito su [cloud.oracle.com](https://cloud.oracle.com) |
| Account DuckDNS | Gratuito su [duckdns.org](https://duckdns.org) |
| Accesso SSH alla VM | Chiave generata da Oracle al momento della creazione |

---

## Fase 1 — Crea la VM Oracle Cloud

1. Accedi a **cloud.oracle.com → Compute → Instances → Create Instance**
2. Configura:
   - **Image:** Ubuntu 22.04
   - **Shape:** `VM.Standard.A1.Flex` — ARM Always Free (1 OCPU, 6 GB RAM)
   - **Boot Volume:** 50 GB (Always Free)
3. Nella sezione **SSH Keys** scarica o incolla la tua chiave pubblica
4. Crea l'istanza e annota l'**IP pubblico** assegnato

---

## Fase 2 — Configura DuckDNS

1. Vai su [duckdns.org](https://duckdns.org) e accedi con GitHub
2. Il sottodominio `horangi` deve puntare all'IP della VM Oracle
3. Clicca **update** e verifica:
   ```bash
   ping horangi.duckdns.org
   # deve rispondere con l'IP della VM
   ```

---

## Fase 3 — Setup della VM

Connettiti via SSH:
```bash
ssh -i chiave-oracle.key ubuntu@<IP-VM>
```

Esegui lo script di setup (installa Docker e apre le porte nel firewall OS):
```bash
curl -fsSL https://raw.githubusercontent.com/FReFraschini/horangi-work-schedule/main/deploy/setup-oracle.sh | bash
```

**Poi:**

**a) Riapri la sessione SSH** (necessario per attivare il gruppo `docker`):
```bash
exit
ssh -i chiave-oracle.key ubuntu@<IP-VM>
```

**b) Apri le porte nel pannello Oracle:**
Vai su **Networking → Virtual Cloud Networks → la tua VCN → Security Lists → Default Security List → Add Ingress Rules** e aggiungi:

| Source CIDR | Protocol | Dest Port |
|-------------|----------|-----------|
| `0.0.0.0/0` | TCP | `80`  |
| `0.0.0.0/0` | TCP | `443` |

---

## Fase 4 — Clona il repository

```bash
git clone https://github.com/FReFraschini/horangi-work-schedule.git
cd horangi-work-schedule
```

---

## Fase 5 — Variabili d'ambiente

### `.env` (root — credenziali Docker/MySQL)
```bash
cp .env.example .env
nano .env
```
```env
NGINX_PORT=80
DB_DATABASE=horangi
DB_USERNAME=horangi
DB_PASSWORD=una_password_sicura
DB_ROOT_PASSWORD=una_root_password_sicura
```

### `code/.env` (Laravel — configurazione applicazione)
```bash
cp deploy/env.prod.example code/.env
nano code/.env
```

Genera `APP_KEY`:
```bash
docker run --rm php:8.3-fpm-alpine php -r \
  "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```
Incolla il valore in `APP_KEY=` e imposta `DB_PASSWORD` uguale a quella del `.env` root.

---

## Fase 6 — Build degli asset frontend

```bash
cd code
npm install
npm run build
cd ..
```

---

## Fase 7 — Certificato SSL (Let's Encrypt)

Inserisci la tua email nello script:
```bash
nano deploy/init-letsencrypt.sh
# imposta: EMAIL="tua@email.com"
```

Poi esegui dalla root del progetto:
```bash
bash deploy/init-letsencrypt.sh
```

Lo script:
1. Crea un certificato temporaneo per far avviare Nginx
2. Richiede il certificato reale a Let's Encrypt via HTTP-01 challenge
3. Ricarica Nginx con il certificato reale

> **Test senza rate limit:** imposta `STAGING=1` prima di eseguire, poi rimetti `0` per il certificato reale.

---

## Fase 8 — Avvio e inizializzazione

```bash
# Avvia tutti i container
docker compose -f docker-compose.prod.yml up -d

# Attendi ~30s che MySQL sia pronto, poi:

# Esegui migrazioni e seed
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force

# Crea il primo utente gestore
docker compose -f docker-compose.prod.yml exec app php artisan user:create

# Ottimizzazioni produzione
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache

# Permessi storage
docker compose -f docker-compose.prod.yml exec app chmod -R 775 storage bootstrap/cache
```

L'app è disponibile su: **https://horangi.duckdns.org**

---

## Aggiornare l'app

```bash
git pull origin main
cd code && npm run build && cd ..
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml restart app
```

---

## Comandi utili

```bash
# Log in tempo reale
docker compose -f docker-compose.prod.yml logs -f

# Log solo di un servizio
docker compose -f docker-compose.prod.yml logs -f app

# Stato dei container
docker compose -f docker-compose.prod.yml ps

# Riavvio singolo servizio
docker compose -f docker-compose.prod.yml restart nginx

# Accesso shell PHP
docker compose -f docker-compose.prod.yml exec app sh

# Rinnovo manuale certificato SSL
docker compose -f docker-compose.prod.yml run --rm certbot renew

# Fermare tutto
docker compose -f docker-compose.prod.yml down

# Fermare tutto e cancellare i volumi (ATTENZIONE: cancella il DB)
docker compose -f docker-compose.prod.yml down -v
```

---

## Architettura Docker in produzione

```
Internet
    │ :80 / :443
    ▼
┌─────────────────────┐
│   Nginx (alpine)    │  – HTTPS, gzip, cache asset statici
│   porta 80 → 443    │  – Reverse proxy verso PHP-FPM
└────────┬────────────┘
         │ FastCGI :9000
         ▼
┌─────────────────────┐
│   PHP 8.3-FPM       │  – Laravel 12, OPcache attivo
│   (Alpine, no xdebug)│  – Processo queue non incluso*
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│   MySQL 8.0         │  – Volume persistente, porta NON esposta
└─────────────────────┘
         +
┌─────────────────────┐
│   Certbot           │  – Rinnovo SSL automatico ogni 12h
└─────────────────────┘
```

> *Per attivare la queue in produzione aggiungere un servizio `worker` al `docker-compose.prod.yml` con comando `php artisan queue:work --tries=3`.
