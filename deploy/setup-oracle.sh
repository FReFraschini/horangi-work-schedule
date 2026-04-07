#!/bin/bash
# Esegui sulla VM Oracle Cloud come utente ubuntu/opc:
# bash setup-oracle.sh
#
# Installa Docker, apre le porte nel firewall OS (80 e 443)
# Le porte vanno aperte anche nel pannello Oracle (VCN Security List)

set -e

echo "=== Setup VM Oracle Cloud per horangi-work-schedule ==="

# ─── 1. Aggiorna il sistema ──────────────────────────────────────────────────
echo "### Aggiorno pacchetti..."
sudo apt-get update -qq && sudo apt-get upgrade -y -qq

# ─── 2. Installa Docker ──────────────────────────────────────────────────────
echo "### Installo Docker..."
sudo apt-get install -y -qq ca-certificates curl gnupg lsb-release git

sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
  | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt-get update -qq
sudo apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-compose-plugin

# ─── 3. Aggiungi utente corrente al gruppo docker ────────────────────────────
sudo usermod -aG docker $USER
echo "### Utente $USER aggiunto al gruppo docker"

# ─── 4. Apri le porte nel firewall OS (iptables) ────────────────────────────
echo "### Apro porte 80 e 443..."
sudo iptables -I INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -I INPUT -p tcp --dport 443 -j ACCEPT

# Rendi le regole permanenti
sudo apt-get install -y -qq iptables-persistent
sudo netfilter-persistent save

echo ""
echo "=== Setup completato ==="
echo ""
echo "PROSSIMI PASSI:"
echo "  1. Chiudi e riapri la sessione SSH (per attivare il gruppo docker)"
echo "  2. Apri le porte 80 e 443 nel pannello Oracle:"
echo "     Networking → Virtual Cloud Networks → la tua VCN → Security Lists → Default → Add Ingress Rules"
echo "     - Source CIDR: 0.0.0.0/0 | Protocol: TCP | Dest Port: 80"
echo "     - Source CIDR: 0.0.0.0/0 | Protocol: TCP | Dest Port: 443"
echo "  3. Clona il repo:"
echo "     git clone https://github.com/FReFraschini/horangi-work-schedule.git"
echo "  4. Segui DEPLOY.md per completare il deployment"
