#!/bin/bash
# Simple MK Net DB backup script.
# Usage: bash scripts/backup_db.sh
# Keeps the last 14 daily backups automatically.

DB_NAME="mknet"
DB_USER="root"
DB_PASS=""              # fill in if your DB has a password
BACKUP_DIR="$(dirname "$0")/../storage/backups"
DATE=$(date +%Y-%m-%d_%H-%M-%S)

mkdir -p "$BACKUP_DIR"

if [ -z "$DB_PASS" ]; then
  mysqldump -u "$DB_USER" "$DB_NAME" > "$BACKUP_DIR/mknet_$DATE.sql"
else
  mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/mknet_$DATE.sql"
fi

# Keep only the last 14 backups
ls -1t "$BACKUP_DIR"/mknet_*.sql 2>/dev/null | tail -n +15 | xargs -r rm --

echo "Backup saved: $BACKUP_DIR/mknet_$DATE.sql"
