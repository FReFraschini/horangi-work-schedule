# Deploy su Oracle Cloud Free + horangi.duckdns.org

Guida completa per deployare l'app su una VM Oracle Cloud Always Free con HTTPS.

---

## Prerequisiti

- Account Oracle Cloud (gratuito su cloud.oracle.com)
- Account DuckDNS già configurato con `horangi.duckdns.org` → IP della VM
- Accesso SSH alla VM

---

## Fase 1 — Crea la VM su Oracle Cloud

1. Vai su **cloud.oracle.com → Compute → Instances → Create Instance**
2. Scegli:
   - **Image:** Ubuntu 22.04
   - **Shape:** `VM.Standard.A1.Flex` (ARM, Always Free) — 1 OCPU, 6 GB RAM
   - **Storage:** 50 GB (Always Free)
3. Scarica la chiave SSH che Oracle genera (serve per connetterti)
4. Crea l'istanza e annota l'**IP pubblico**

---

## Fase 2 — Configura DuckDNS

1. Vai su [duckdns.org](https://duckdns.org) e accedi
2. Il dominio `horangi` dovrebbe già essere registrato
3. Aggiorna l'IP con quello della VM Oracle appena creata
4. Verifica: `ping horangi.duckdns.org` deve rispondere con l'IP della VM

---

## Fase 3 — Setup della VM

Connettiti via SSH:
```bash
ssh -i chiave-oracle.key ubuntu@<IP-DELLA-VM>
```

Scarica ed esegui lo script di setup:
```bash
curl -o setup-oracle.sh https://raw.githubusercontent.com/FReFraschini/horangi-work-schedule/main/deploy/setup-oracle.sh
bash setup-oracle.sh
```

**Poi:**
1. **Chiudi e riapri la sessione SSH** (necessario per il gruppo docker)
2. Apri le porte nel pannello Oracle:
   - Vai su **Networking → Virtual Cloud Networks → la tua VCN → Security Lists → Default Security List**
   - **Add Ingress Rules** — aggiungi due regole:
     - Source CIDR: `0.0.0.0/0` | TCP | Dest Port: `80`
     - Source CIDR: `0.0.0.0/0` | TCP | Dest Port: `443`

---

## Fase 4 — Clona il progetto

```bash
git clone https://github.com/FReFraschini/horangi-work-schedule.git
cd horangi-work-schedule
```

---

## Fase 5 — Configura le variabili d'ambiente

### File `.env` (root del progetto — credenziali Docker)
```bash
cp .env.example .env
nano .env
```
Imposta:
```env
NGINX_PORT=80
DB_DATABASE=horangi
DB_USERNAME=horangi
DB_PASSWORD=scegli_password_sicura
DB_ROOT_PASSWORD=scegli_root_password_sicura
```

### File `code/.env` (Laravel)
```bash
cp deploy/env.prod.example code/.env
nano code/.env
```
- Imposta `DB_PASSWORD` uguale a quella del file `.env` root
- Genera `APP_KEY`:
  ```bash
  docker run --rm php:8.3-fpm-alpine php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
  ```
  Copia il valore in `APP_KEY=`

---

## Fase 6 — Builda i frontend assets

Gli asset JavaScript/CSS vanno buildati prima del deploy:
```bash
cd code
npm install
npm run build
cd ..
```

---

## Fase 7 — Ottieni il certificato SSL

Modifica `deploy/init-letsencrypt.sh` e inserisci la tua **email** nella variabile `EMAIL`:
```bash
nano deploy/init-letsencrypt.sh
```

Poi esegui (dalla root del progetto):
```bash
bash deploy/init-letsencrypt.sh
```

Questo script:
1. Crea un certificato temporaneo per far partire nginx
2. Richiede il vero certificato a Let's Encrypt
3. Ricarica nginx con il certificato reale

---

## Fase 8 — Avvia tutto e inizializza il DB

```bash
# Avvia tutti i container
docker compose -f docker-compose.prod.yml up -d

# Aspetta che il DB sia pronto (circa 30 secondi), poi:
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force

# Ottimizza Laravel per la produzione
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache

# Imposta i permessi sullo storage
docker compose -f docker-compose.prod.yml exec app chmod -R 775 storage bootstrap/cache
```

---

## Risultato

L'app è disponibile su: **https://horangi.duckdns.org**

Il certificato SSL si rinnova automaticamente ogni 12 ore (se necessario).

---

## Comandi utili

```bash
# Vedere i log
docker compose -f docker-compose.prod.yml logs -f

# Riavviare un servizio
docker compose -f docker-compose.prod.yml restart nginx

# Aggiornare l'app dopo un git pull
git pull
cd code && npm run build && cd ..
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml restart app

# Fermare tutto
docker compose -f docker-compose.prod.yml down
```
