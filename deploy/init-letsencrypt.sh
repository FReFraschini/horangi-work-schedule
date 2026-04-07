#!/bin/bash
# Esegui dalla root del progetto: bash deploy/init-letsencrypt.sh
# Ottiene il primo certificato Let's Encrypt per horangi.duckdns.org

set -e

DOMAIN="horangi.duckdns.org"
EMAIL=""           # <-- inserisci la tua email (per notifiche scadenza)
STAGING=0          # metti 1 per testare senza rate limit, poi rimetti 0 per il cert reale

# ─── Verifica prerequisiti ───────────────────────────────────────────────────
if [ -z "$EMAIL" ]; then
  echo "ERRORE: inserisci la tua email in questo script (variabile EMAIL)"
  exit 1
fi

if ! command -v docker &> /dev/null; then
  echo "ERRORE: Docker non trovato. Esegui prima deploy/setup-oracle.sh"
  exit 1
fi

# ─── Scarica parametri SSL consigliati da Let's Encrypt ─────────────────────
echo "### Scarico parametri SSL..."
mkdir -p ./certbot/conf
curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf \
  > ./certbot/conf/options-ssl-nginx.conf
curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/_internal/ssl-dhparams.pem \
  > ./certbot/conf/ssl-dhparams.pem

# ─── Crea certificato temporaneo (dummy) per far partire nginx ───────────────
echo "### Creo certificato temporaneo per $DOMAIN..."
mkdir -p ./certbot/conf/live/$DOMAIN
docker run --rm -v "$(pwd)/certbot/conf:/etc/letsencrypt" \
  --entrypoint openssl certbot/certbot \
  req -x509 -nodes -newkey rsa:2048 -days 1 \
  -keyout /etc/letsencrypt/live/$DOMAIN/privkey.pem \
  -out /etc/letsencrypt/live/$DOMAIN/fullchain.pem \
  -subj "/CN=localhost" 2>/dev/null

# ─── Avvia solo nginx con il certificato dummy ───────────────────────────────
echo "### Avvio nginx..."
docker compose -f docker-compose.prod.yml up -d nginx

# ─── Rimuovi il certificato dummy ───────────────────────────────────────────
echo "### Rimuovo certificato temporaneo..."
docker run --rm -v "$(pwd)/certbot/conf:/etc/letsencrypt" \
  --entrypoint rm certbot/certbot \
  -rf /etc/letsencrypt/live/$DOMAIN \
     /etc/letsencrypt/archive/$DOMAIN \
     /etc/letsencrypt/renewal/$DOMAIN.conf

# ─── Ottieni il certificato reale da Let's Encrypt ──────────────────────────
echo "### Richiedo certificato reale a Let's Encrypt..."

STAGING_FLAG=""
if [ "$STAGING" = "1" ]; then
  STAGING_FLAG="--staging"
fi

docker compose -f docker-compose.prod.yml run --rm certbot \
  certonly --webroot \
  --webroot-path=/var/www/certbot \
  $STAGING_FLAG \
  --email $EMAIL \
  --agree-tos \
  --no-eff-email \
  -d $DOMAIN

# ─── Ricarica nginx con il certificato reale ─────────────────────────────────
echo "### Ricarico nginx..."
docker compose -f docker-compose.prod.yml exec nginx nginx -s reload

echo ""
echo "✓ Certificato SSL ottenuto per $DOMAIN"
echo "  Avvia l'app completa con:"
echo "  docker compose -f docker-compose.prod.yml up -d"
