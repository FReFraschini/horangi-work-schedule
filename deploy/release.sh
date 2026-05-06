#!/usr/bin/env bash
# deploy/release.sh — Script di rilascio per produzione
# Esegui dalla root del progetto: bash deploy/release.sh
#
# Flags opzionali:
#   --skip-build      Salta npm ci + npm run build (frontend già compilato)
#   --skip-optimize   Salta config:cache / route:cache / view:cache / event:cache
#   --no-pull         Non esegue git pull (deploy del codice già presente)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
CODE_DIR="$ROOT_DIR/code"
COMPOSE_FILE="$ROOT_DIR/docker-compose.prod.yml"

# ── Flags ─────────────────────────────────────────────────────────────────────
SKIP_BUILD=0; SKIP_OPTIMIZE=0; NO_PULL=0
for arg in "$@"; do
    case "$arg" in
        --skip-build)    SKIP_BUILD=1    ;;
        --skip-optimize) SKIP_OPTIMIZE=1 ;;
        --no-pull)       NO_PULL=1       ;;
        *) echo "Flag sconosciuto: $arg"; exit 1 ;;
    esac
done

# ── Colori ────────────────────────────────────────────────────────────────────
GRN='\033[0;32m'; YEL='\033[1;33m'; BLU='\033[0;34m'; RED='\033[0;31m'; NC='\033[0m'
step()  { echo;   printf "${BLU}▶  %s${NC}\n" "$*"; }
ok()    {         printf "${GRN}   ✓ %s${NC}\n" "$*"; }
warn()  {         printf "${YEL}   ⚠ %s${NC}\n" "$*"; }
die()   {         printf "${RED}✗  %s${NC}\n"   "$*"; exit 1; }

# ── Prerequisiti ──────────────────────────────────────────────────────────────
step "Verifica prerequisiti"
command -v docker >/dev/null 2>&1 || die "Docker non trovato"
command -v git    >/dev/null 2>&1 || die "git non trovato"
[ "$SKIP_BUILD" -eq 0 ] && { command -v npm >/dev/null 2>&1 || die "npm non trovato (usa --skip-build se non necessario)"; }

# Lo stack prod deve essere già in esecuzione
docker compose -f "$COMPOSE_FILE" ps --quiet app 2>/dev/null | grep -q . \
    || die "Il container 'app' non è in esecuzione.\nAvvia prima lo stack con:\n  docker compose -f docker-compose.prod.yml up -d"
ok "Prerequisiti OK"

# ── Rimetti l'app online in caso di errore ────────────────────────────────────
_on_error() {
    echo
    warn "Errore rilevato — riportando l'app online..."
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan up 2>/dev/null || true
    echo
    printf "${RED}✗  Release interrotta. Controlla l'output sopra.${NC}\n"
}
trap _on_error ERR

# ── 1. Git pull ───────────────────────────────────────────────────────────────
if [ "$NO_PULL" -eq 0 ]; then
    step "Aggiornamento codice"
    PREV_HEAD=$(git -C "$ROOT_DIR" rev-parse HEAD)
    git -C "$ROOT_DIR" pull --ff-only || die "git pull fallito — risolvi i conflitti e riprova"
    NEW_HEAD=$(git -C "$ROOT_DIR" rev-parse HEAD)

    if [ "$PREV_HEAD" = "$NEW_HEAD" ]; then
        warn "Nessuna modifica da deployare (HEAD invariato); proseguo comunque"
    else
        ok "$(git -C "$ROOT_DIR" log --oneline -1)"
    fi

    CHANGED=$(git -C "$ROOT_DIR" diff --name-only "$PREV_HEAD" "$NEW_HEAD" 2>/dev/null || true)
else
    warn "--no-pull: uso il codice già presente ($(git -C "$ROOT_DIR" log --oneline -1))"
    CHANGED=""
fi

# ── 2. Rebuild immagine Docker se il Dockerfile è cambiato ───────────────────
if echo "$CHANGED" | grep -q "^\.docker/"; then
    step "Dockerfile modificato — ricostruzione immagine"
    docker compose -f "$COMPOSE_FILE" build --no-cache app
    ok "Immagine ricostruita"
fi

# ── 3. Build frontend ─────────────────────────────────────────────────────────
if [ "$SKIP_BUILD" -eq 0 ]; then
    step "Build frontend"

    if echo "$CHANGED" | grep -q "code/package-lock\.json" || [ ! -d "$CODE_DIR/node_modules" ]; then
        ok "Aggiornamento dipendenze npm (lockfile cambiato)"
        npm --prefix "$CODE_DIR" ci --silent
    fi

    npm --prefix "$CODE_DIR" run build
    ok "Frontend compilato → public/build/"
else
    warn "Build frontend saltata (--skip-build)"
fi

# ── 4. Maintenance mode ON ────────────────────────────────────────────────────
step "Attivazione maintenance mode"
BYPASS_SECRET=$(openssl rand -hex 16)
docker compose -f "$COMPOSE_FILE" exec -T app \
    php artisan down --render="errors.503" --retry=60 --secret="$BYPASS_SECRET"
printf "${YEL}   Bypass durante la manutenzione → https://<dominio>/%s${NC}\n" "$BYPASS_SECRET"
ok "Maintenance mode attivo"

# ── 5. Dipendenze PHP ─────────────────────────────────────────────────────────
if echo "$CHANGED" | grep -q "code/composer\.lock" || [ ! -d "$CODE_DIR/vendor" ]; then
    step "Aggiornamento dipendenze PHP"
    docker compose -f "$COMPOSE_FILE" exec -T app \
        composer install --no-dev --optimize-autoloader --no-interaction --quiet
    ok "Composer completato"
fi

# ── 6. Pulizia cache ──────────────────────────────────────────────────────────
step "Pulizia cache Laravel"
docker compose -f "$COMPOSE_FILE" exec -T app php artisan config:clear  --quiet
docker compose -f "$COMPOSE_FILE" exec -T app php artisan route:clear   --quiet
docker compose -f "$COMPOSE_FILE" exec -T app php artisan view:clear    --quiet
docker compose -f "$COMPOSE_FILE" exec -T app php artisan event:clear   --quiet
docker compose -f "$COMPOSE_FILE" exec -T app php artisan cache:clear   --quiet
# Svuota i file di sessione compilati (se usi driver file)
docker compose -f "$COMPOSE_FILE" exec -T app \
    find /var/www/storage/framework/sessions -type f -not -name '.gitignore' -delete 2>/dev/null || true
ok "Cache pulita"

# ── 7. Migrazioni ─────────────────────────────────────────────────────────────
step "Migrazioni database"
docker compose -f "$COMPOSE_FILE" exec -T app \
    php artisan migrate --force --no-interaction
ok "Migrazioni completate"

# ── 8. Ottimizzazione ─────────────────────────────────────────────────────────
if [ "$SKIP_OPTIMIZE" -eq 0 ]; then
    step "Ottimizzazione Laravel"
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan config:cache  --quiet
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan route:cache   --quiet
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan view:cache    --quiet
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan event:cache   --quiet
    ok "config / route / view / event cache generati"
else
    warn "Ottimizzazione saltata (--skip-optimize)"
fi

# ── 9. Riavvio PHP-FPM per reset OPcache ─────────────────────────────────────
step "Reset OPcache (ricarico PHP-FPM workers)"
# USR2 → graceful reload del master php-fpm senza drop di connessioni
docker compose -f "$COMPOSE_FILE" exec -T app kill -USR2 1 2>/dev/null \
    || { warn "kill -USR2 non riuscito, riavvio container app"; docker compose -f "$COMPOSE_FILE" restart app; }
sleep 2   # attendo che i nuovi workers siano pronti
ok "OPcache resettato"

# ── 10. Riavvio queue workers ─────────────────────────────────────────────────
step "Riavvio queue workers"
docker compose -f "$COMPOSE_FILE" exec -T app php artisan queue:restart
ok "Workers riavviati al prossimo job disponibile"

# ── 11. Maintenance mode OFF ──────────────────────────────────────────────────
step "Ripristino applicazione"
docker compose -f "$COMPOSE_FILE" exec -T app php artisan up
ok "Applicazione online"

# ── Riepilogo ─────────────────────────────────────────────────────────────────
echo
printf "${GRN}══════════════════════════════════════════════════${NC}\n"
printf "${GRN}  ✓  Release completata con successo!             ${NC}\n"
printf "${GRN}══════════════════════════════════════════════════${NC}\n"
printf "  Commit:   %s\n" "$(git -C "$ROOT_DIR" log --oneline -1)"
printf "  Data/ora: %s\n" "$(date '+%Y-%m-%d %H:%M:%S')"
echo
