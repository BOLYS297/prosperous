#!/usr/bin/env bash
# =====================================================================
#  Sauvegarde de la base MySQL (dump compressé) — à exécuter SUR le VPS.
#  Usage :
#     bash backup.sh              # sauvegarde "manual"
#     bash backup.sh pre-deploy   # étiquette la sauvegarde (ex. avant déploiement)
#
#  Les dumps sont écrits HORS du dépôt (/opt/prosperous-backups) : ils ne sont
#  donc jamais touchés par "git reset --hard" ni par un rebuild Docker.
# =====================================================================
set -uo pipefail

APP_DIR="${APP_DIR:-/opt/prosperous}"
BACKUP_DIR="${BACKUP_DIR:-/opt/prosperous-backups}"
RETENTION="${RETENTION:-30}"   # nombre de sauvegardes à conserver
LABEL="${1:-manual}"

cd "$APP_DIR" || { echo "Répertoire $APP_DIR introuvable" >&2; exit 1; }
mkdir -p "$BACKUP_DIR"

# Identifiants lus depuis le .env (sans le sourcer, pour éviter tout effet de bord)
DB_ROOT_PASSWORD=$(grep -E '^DB_ROOT_PASSWORD=' .env | head -1 | cut -d= -f2- | tr -d "\"'")
DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | head -1 | cut -d= -f2- | tr -d "\"'")

if [ -z "$DB_ROOT_PASSWORD" ] || [ -z "$DB_DATABASE" ]; then
    echo "ERREUR: DB_ROOT_PASSWORD ou DB_DATABASE introuvable dans .env" >&2
    exit 1
fi

TS=$(date +%Y%m%d_%H%M%S)
OUT="$BACKUP_DIR/${LABEL}-${TS}.sql.gz"

echo "==> Sauvegarde de '$DB_DATABASE' -> $OUT"
if docker compose exec -T db mysqldump -u root -p"$DB_ROOT_PASSWORD" \
        --single-transaction --routines --triggers --no-tablespaces "$DB_DATABASE" \
        2>/dev/null | gzip > "$OUT"; then

    # Un dump vide/échoué fait quelques octets seulement -> on le rejette.
    SIZE=$(stat -c%s "$OUT" 2>/dev/null || echo 0)
    if [ "$SIZE" -lt 500 ]; then
        echo "ERREUR: sauvegarde vide ou invalide (${SIZE} octets), suppression." >&2
        rm -f "$OUT"
        exit 1
    fi

    echo "    OK : $(du -h "$OUT" | cut -f1)"
else
    echo "ERREUR: mysqldump a échoué." >&2
    rm -f "$OUT"
    exit 1
fi

# Rétention : conserver les N sauvegardes les plus récentes, supprimer le reste.
ls -1t "$BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -n +"$((RETENTION + 1))" | xargs -r rm -f

echo "==> Sauvegardes conservées : $(ls -1 "$BACKUP_DIR"/*.sql.gz 2>/dev/null | wc -l) (max $RETENTION)"
