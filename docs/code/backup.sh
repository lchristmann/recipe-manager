#!/bin/sh

BACKUP_DIR="/opt/recipe-manager-backups/$(date +%Y-%m-%d_%H-%M)"
mkdir -p "$BACKUP_DIR"

echo "[INFO] Starting backup: $(date)"

# 1) Database backup (compressed SQL dump)
docker compose exec -T postgres \
  pg_dump --format=custom -d app -U laravel > "$BACKUP_DIR/postgres.dump"

echo "[INFO] Database dump completed"

# 2) Storage backup (uploaded files)
docker run --rm \
  -v recipe-manager_laravel-storage-production:/data \
  -v "$BACKUP_DIR:/backup" \
  ubuntu tar czf "/backup/storage-volume.tar.gz" /data

echo "[INFO] Storage backup completed"
echo "[INFO] Backup stored at: $BACKUP_DIR"

# 3) Delete backups older than 30 days
find /opt/recipe-manager-backups -mindepth 1 -maxdepth 1 -type d -mtime +30 -exec rm -rf {} \;
