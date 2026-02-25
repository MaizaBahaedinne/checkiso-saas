#!/bin/bash
# =============================================================================
# deploy.sh — Script de déploiement CheckISO
# Usage: bash deploy.sh
# =============================================================================

set -e
APP_DIR="/home/persomy.com/checkiso.persomy.com"
cd "$APP_DIR"

echo "=== [1/5] Git pull ==="
git pull origin main

echo "=== [2/5] CI4 migrations ==="
php spark migrate --no-interaction

echo "=== [3/5] CI4 cache clear ==="
php spark cache:clear

echo "=== [4/5] OPcache reset (kill LSPHP workers) ==="
# Kill all LSPHP worker processes — OpenLiteSpeed respawns them fresh
if pkill -9 lsphp 2>/dev/null; then
    echo "    LSPHP workers killed — OPcache cleared."
    sleep 1
else
    echo "    Aucun process lsphp actif trouvé."
fi

echo "=== [5/5] Permissions writable/ ==="
chmod -R 755 writable/

echo ""
echo "✅ Déploiement terminé."
echo "   App: https://checkiso.persomy.com"
