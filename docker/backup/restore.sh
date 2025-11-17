#!/bin/sh
set -e

# Check if backup file is provided
if [ -z "$1" ]; then
    echo "Usage: ./restore.sh <backup_file.sql.gz>"
    echo "Available backups:"
    ls -lh /backups/*.sql.gz
    exit 1
fi

BACKUP_FILE=$1

if [ ! -f "${BACKUP_FILE}" ]; then
    echo "Error: Backup file not found: ${BACKUP_FILE}"
    exit 1
fi

echo "[$(date)] Starting database restore from: ${BACKUP_FILE}"

# Confirm restoration
read -p "This will REPLACE the current database. Continue? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

# Drop existing connections
PGPASSWORD=$POSTGRES_PASSWORD psql -h $PGHOST -U $POSTGRES_USER -d postgres << EOF
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE datname = '$POSTGRES_DB' AND pid <> pg_backend_pid();
EOF

# Drop and recreate database
PGPASSWORD=$POSTGRES_PASSWORD psql -h $PGHOST -U $POSTGRES_USER -d postgres << EOF
DROP DATABASE IF EXISTS $POSTGRES_DB;
CREATE DATABASE $POSTGRES_DB;
EOF

# Restore backup
gunzip -c ${BACKUP_FILE} | PGPASSWORD=$POSTGRES_PASSWORD pg_restore \
    -h $PGHOST \
    -U $POSTGRES_USER \
    -d $POSTGRES_DB \
    --verbose

if [ $? -eq 0 ]; then
    echo "[$(date)] Database restored successfully!"
else
    echo "[$(date)] ERROR: Restore failed!"
    exit 1
fi
