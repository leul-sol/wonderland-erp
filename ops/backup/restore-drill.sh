#!/bin/sh
# Restore drill — validates latest backup without touching production databases.
set -eu

MYSQL_HOST="${MYSQL_HOST:-localhost}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-root_secret}"
TEST_DB="wh_restore_drill"

archive="${1:-}"
if [ -z "$archive" ]; then
    archive="$(ls -1t /backups/wonderland-mysql-*.tar.gz 2>/dev/null | head -n 1 || true)"
fi

if [ -z "$archive" ] || [ ! -f "$archive" ]; then
    echo "[drill] no backup archive found in /backups" >&2
    exit 1
fi

workdir="$(mktemp -d)"
trap 'rm -rf "$workdir"' EXIT

echo "[drill] extracting $(basename "$archive")"
tar -xzf "$archive" -C "$workdir"

sample="$(ls -1 "$workdir"/*.sql 2>/dev/null | head -n 1 || true)"
if [ -z "$sample" ]; then
    echo "[drill] archive contains no SQL dumps" >&2
    exit 1
fi

echo "[drill] importing sample into temporary database ${TEST_DB}"
mysql -h "$MYSQL_HOST" -uroot -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS \`${TEST_DB}\`; CREATE DATABASE \`${TEST_DB}\`;"
sed "s/wh_s1_db/${TEST_DB}/g; s/wh_s2_db/${TEST_DB}/g; s/wh_s3_db/${TEST_DB}/g; s/wh_s4_db/${TEST_DB}/g" "$sample" \
    | mysql -h "$MYSQL_HOST" -uroot -p"$MYSQL_ROOT_PASSWORD"

tables="$(mysql -h "$MYSQL_HOST" -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${TEST_DB}';")"

mysql -h "$MYSQL_HOST" -uroot -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE \`${TEST_DB}\`;"

if [ "$tables" -lt 1 ]; then
    echo "[drill] FAILED - restored database has no tables" >&2
    exit 1
fi

echo "[drill] PASS - archive valid (${tables} tables in sample restore)"
