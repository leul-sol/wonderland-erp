#!/bin/sh
set -eu

MYSQL_HOST="${MYSQL_HOST:-wh-mysql}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-root_secret}"
BACKUP_DIR="${BACKUP_DIR:-/backups}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"

DATABASES="wh_s1_db wh_s2_db wh_s3_db wh_s4_db"

mkdir -p "$BACKUP_DIR"

run_backup() {
    stamp="$(date -u +%Y%m%dT%H%M%SZ)"
    archive="${BACKUP_DIR}/wonderland-mysql-${stamp}.tar.gz"
    workdir="$(mktemp -d)"

    echo "[backup] starting ${stamp}"

    for db in $DATABASES; do
        echo "[backup] dumping ${db}"
        mysqldump \
            -h "$MYSQL_HOST" \
            -uroot \
            -p"$MYSQL_ROOT_PASSWORD" \
            --single-transaction \
            --routines \
            --triggers \
            --databases "$db" \
            > "${workdir}/${db}.sql"
    done

    tar -czf "$archive" -C "$workdir" .
    rm -rf "$workdir"

    echo "[backup] wrote ${archive}"

    find "$BACKUP_DIR" -type f -name 'wonderland-mysql-*.tar.gz' -mtime +"$RETENTION_DAYS" -delete 2>/dev/null || true
}

if [ "${1:-}" = "once" ]; then
    run_backup
    exit 0
fi

interval_hours="${BACKUP_INTERVAL_HOURS:-24}"
interval_seconds=$((interval_hours * 3600))

echo "[backup] loop every ${interval_hours}h (retention ${RETENTION_DAYS}d)"
while true; do
    run_backup || echo "[backup] failed — will retry next interval"
    sleep "$interval_seconds"
done
