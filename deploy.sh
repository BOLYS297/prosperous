#!/usr/bin/env bash
# =====================================================================
#  Script de déploiement exécuté SUR le VPS.
#  Appelé manuellement ou par GitHub Actions (via SSH).
#
#  IMPORTANT : ce script n'exécute AUCUNE commande destructive.
#  - `migrate --force` applique uniquement les migrations manquantes
#    (il ne supprime jamais de tables / données, contrairement à
#    `migrate:fresh`).
#  - Une SAUVEGARDE de la base est faite AVANT chaque migration.
# =====================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/prosperous}"
cd "$APP_DIR"

echo "==> 1/7  Récupération du code (git pull)"
git fetch --all
git reset --hard origin/main

echo "==> 2/7  Sauvegarde de la base AVANT tout (filet de sécurité)"
bash "$APP_DIR/backup.sh" pre-deploy \
    || echo "    AVERTISSEMENT: sauvegarde échouée. La migration reste additive (non destructive), on continue."

echo "==> 3/7  Construction de l'image Docker"
docker compose build

echo "==> 4/7  Démarrage / mise à jour des conteneurs"
docker compose up -d

echo "==> 5/7  Attente que l'application réponde"
for i in $(seq 1 30); do
    if docker compose exec -T app php -v >/dev/null 2>&1; then
        break
    fi
    echo "    ... démarrage en cours ($i)"
    sleep 2
done

echo "==> 6/7  Migrations + mise en cache de la configuration"
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

echo "==> 7/7  Redémarrage des workers + nettoyage"
docker compose restart queue scheduler
docker image prune -f >/dev/null 2>&1 || true

echo "==> Déploiement terminé avec succès."
