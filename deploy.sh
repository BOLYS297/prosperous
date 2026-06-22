#!/usr/bin/env bash
# =====================================================================
#  Script de déploiement exécuté SUR le VPS.
#  Appelé manuellement ou par GitHub Actions (via SSH).
# =====================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/prosperous}"
cd "$APP_DIR"

echo "==> 1/6  Récupération du code (git pull)"
git fetch --all
git reset --hard origin/main

echo "==> 2/6  Construction de l'image Docker"
docker compose build

echo "==> 3/6  Démarrage / mise à jour des conteneurs"
docker compose up -d

echo "==> 4/6  Attente que l'application réponde"
for i in $(seq 1 30); do
    if docker compose exec -T app php -v >/dev/null 2>&1; then
        break
    fi
    echo "    ... démarrage en cours ($i)"
    sleep 2
done

echo "==> 5/6  Migrations + mise en cache de la configuration"
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

echo "==> 6/6  Redémarrage des workers + nettoyage"
docker compose restart queue scheduler
docker image prune -f >/dev/null 2>&1 || true

echo "==> Déploiement terminé avec succès."
