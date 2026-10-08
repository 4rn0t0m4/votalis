#!/usr/bin/env sh
# Sauvegarde chiffrée de PostgreSQL (CDC 7.1 : quotidienne, chiffrée, copie hors ligne).
# Usage : infra/backup/backup.sh <fichier-compose> <env-file> <dossier-destination>
# La phrase de passe est lue dans $BACKUP_PASSPHRASE_FILE (hors dépôt, droits 600).
# Chiffrement : openssl AES-256-CBC, dérivation PBKDF2 (600 000 itérations). Rotation : $BACKUP_KEEP_DAYS (30).
set -eu

COMPOSE_FILE="${1:?fichier compose}"
ENV_FILE="${2:?env-file}"
DEST="${3:?dossier de destination}"
PASS_FILE="${BACKUP_PASSPHRASE_FILE:?BACKUP_PASSPHRASE_FILE manquant}"
KEEP_DAYS="${BACKUP_KEEP_DAYS:-30}"

[ -r "$PASS_FILE" ] || { echo "Phrase de passe illisible : $PASS_FILE" >&2; exit 1; }
mkdir -p "$DEST"

STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
OUT="$DEST/votalis-$STAMP.sql.gz.enc"

docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec -T db \
    sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --no-owner --no-privileges' \
  | gzip -9 \
  | openssl enc -aes-256-cbc -pbkdf2 -iter 600000 -salt -pass "file:$PASS_FILE" -out "$OUT"

chmod 600 "$OUT"
sha256sum "$OUT" > "$OUT.sha256"

# Rotation locale ; la copie hors ligne (rclone, rsync vers un stockage objet européen) se fait ensuite.
find "$DEST" -name 'votalis-*.sql.gz.enc*' -mtime "+$KEEP_DAYS" -delete

echo "Sauvegarde écrite : $OUT ($(du -h "$OUT" | cut -f1))"
