#!/bin/sh
set -eu

MYSQL_HOST="${MYSQL_HOST:-localhost}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-root_secret}"

if [ $# -lt 1 ]; then
    echo "Usage: restore-mysql.sh <archive.tar.gz> [--yes]" >&2
    exit 1
fi

archive="$1"
confirm="${2:-}"

if [ ! -f "$archive" ]; then
    echo "Archive not found: $archive" >&2
    exit 1
fi

workdir="$(mktemp -d)"
trap 'rm -rf "$workdir"' EXIT

tar -xzf "$archive" -C "$workdir"

for sql in "$workdir"/*.sql; do
    [ -f "$sql" ] || continue
    echo "[restore] applying $(basename "$sql")"
    mysql -h "$MYSQL_HOST" -uroot -p"$MYSQL_ROOT_PASSWORD" < "$sql"
done

echo "[restore] complete"
