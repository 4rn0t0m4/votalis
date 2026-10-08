#!/usr/bin/env sh
# Restauration d'une sauvegarde chiffrée dans une base cible (test mensuel ou reprise).
# Usage : infra/backup/restore.sh <fichier-compose> <env-file> <sauvegarde.sql.gz.enc> [base-cible]
# Par défaut, restaure dans une base `votalis_restore_test` créée à la volée : la production n'est jamais écrasée sans le dire.
set -eu

COMPOSE_FILE="${1:?fichier compose}"
ENV_FILE="${2:?env-file}"
FILE="${3:?fichier de sauvegarde}"
TARGET_DB="${4:-votalis_restore_test}"
PASS_FILE="${BACKUP_PASSPHRASE_FILE:?BACKUP_PASSPHRASE_FILE manquant}"

[ -f "$FILE.sha256" ] && (cd "$(dirname "$FILE")" && sha256sum -c "$(basename "$FILE").sha256")

compose() { docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec -T db "$@"; }

compose sh -c "psql -U \"\$POSTGRES_USER\" -d postgres -v ON_ERROR_STOP=1 -c 'DROP DATABASE IF EXISTS $TARGET_DB' -c 'CREATE DATABASE $TARGET_DB'"

openssl enc -d -aes-256-cbc -pbkdf2 -iter 600000 -pass "file:$PASS_FILE" -in "$FILE" \
  | gunzip \
  | compose sh -c "psql -U \"\$POSTGRES_USER\" -d $TARGET_DB -v ON_ERROR_STOP=1 -q"

compose sh -c "psql -U \"\$POSTGRES_USER\" -d $TARGET_DB -tAc \"SELECT 'users: ' || count(*) FROM users UNION ALL SELECT 'proposals: ' || count(*) FROM proposals UNION ALL SELECT 'votes: ' || count(*) FROM votes UNION ALL SELECT 'moderation_log: ' || count(*) FROM moderation_log\""

echo "Restauration terminée dans $TARGET_DB."
