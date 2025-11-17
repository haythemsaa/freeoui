#!/bin/sh
set -e

# Configuration
BACKUP_DIR="/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="freeoui_backup_${TIMESTAMP}.sql.gz"
KEEP_DAYS=30

# Create backup directory if not exists
mkdir -p ${BACKUP_DIR}

echo "[$(date)] Starting database backup..."

# Create backup
PGPASSWORD=$POSTGRES_PASSWORD pg_dump \
    -h $PGHOST \
    -U $POSTGRES_USER \
    -d $POSTGRES_DB \
    --format=custom \
    --compress=9 \
    --verbose \
    | gzip > ${BACKUP_DIR}/${BACKUP_FILE}

if [ $? -eq 0 ]; then
    echo "[$(date)] Backup completed: ${BACKUP_FILE}"

    # Calculate backup size
    BACKUP_SIZE=$(du -h ${BACKUP_DIR}/${BACKUP_FILE} | cut -f1)
    echo "[$(date)] Backup size: ${BACKUP_SIZE}"
else
    echo "[$(date)] ERROR: Backup failed!"
    exit 1
fi

# Clean old backups
echo "[$(date)] Cleaning backups older than ${KEEP_DAYS} days..."
find ${BACKUP_DIR} -name "freeoui_backup_*.sql.gz" -type f -mtime +${KEEP_DAYS} -delete

# List current backups
echo "[$(date)] Current backups:"
ls -lh ${BACKUP_DIR}/freeoui_backup_*.sql.gz

echo "[$(date)] Backup process completed successfully!"
