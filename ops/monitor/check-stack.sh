#!/bin/sh
set -eu

GATEWAY_URL="${GATEWAY_URL:-http://wh-gateway}"
MYSQL_HOST="${MYSQL_HOST:-wh-mysql}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-root_secret}"
FAILED_OUTBOX_THRESHOLD="${FAILED_OUTBOX_THRESHOLD:-1}"
LOG_DIR="${LOG_DIR:-/var/log/wh-monitor}"
ALERT_WEBHOOK_URL="${ALERT_WEBHOOK_URL:-}"

mkdir -p "$LOG_DIR"
logfile="${LOG_DIR}/check.log"

issues=""

log() {
    line="[$(date -u +%Y-%m-%dT%H:%M:%SZ)] $1"
    echo "$line"
    echo "$line" >> "$logfile"
}

check_http() {
    name="$1"
    url="$2"
    if curl -sf --max-time 15 "$url" >/dev/null; then
        log "OK  ${name} ${url}"
    else
        log "FAIL ${name} ${url}"
        issues="${issues}${name} unreachable\n"
    fi
}

check_outbox() {
    db="$1"
    failed="$(mysql -h "$MYSQL_HOST" -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e \
        "SELECT COUNT(*) FROM ${db}.event_outbox WHERE status='failed';" 2>/dev/null || echo "ERR")"

    if [ "$failed" = "ERR" ]; then
        log "WARN ${db} event_outbox query failed (table may not exist yet)"
        return
    fi

    if [ "$failed" -ge "$FAILED_OUTBOX_THRESHOLD" ]; then
        log "FAIL ${db} has ${failed} failed outbox row(s)"
        issues="${issues}${db} outbox failed=${failed}\n"
    else
        log "OK  ${db} outbox failed=${failed}"
    fi
}

check_failed_jobs() {
    db="$1"
    count="$(mysql -h "$MYSQL_HOST" -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e \
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${db}' AND table_name='failed_jobs';" 2>/dev/null || echo 0)"

    if [ "$count" != "1" ]; then
        return
    fi

    failed="$(mysql -h "$MYSQL_HOST" -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e \
        "SELECT COUNT(*) FROM ${db}.failed_jobs;" 2>/dev/null || echo 0)"

    if [ "$failed" -ge 1 ]; then
        log "FAIL ${db} has ${failed} failed_jobs row(s)"
        issues="${issues}${db} failed_jobs=${failed}\n"
    else
        log "OK  ${db} failed_jobs=0"
    fi
}

send_alert() {
    body="$1"
    if [ -z "$ALERT_WEBHOOK_URL" ]; then
        return
    fi

    payload="$(printf '{"text":"Wonderland ERP monitor alert\\n%s"}' "$(printf '%b' "$body" | sed 's/"/\\"/g')")"
    curl -sf -X POST -H 'Content-Type: application/json' -d "$payload" "$ALERT_WEBHOOK_URL" >/dev/null \
        || log "WARN webhook delivery failed"
}

log "=== stack check ==="

check_http "gateway" "${GATEWAY_URL}/health"
check_http "s1" "${GATEWAY_URL}/s1/api/v1/health"
check_http "s2" "${GATEWAY_URL}/s2/api/v1/health"
check_http "s3" "${GATEWAY_URL}/s3/api/v1/health"
check_http "s4" "${GATEWAY_URL}/s4/api/v1/health"
check_http "portal" "${GATEWAY_URL}/up"

for db in wh_s1_db wh_s2_db wh_s3_db wh_s4_db; do
    check_outbox "$db"
    check_failed_jobs "$db"
done

if [ -n "$issues" ]; then
    log "RESULT: ALERT"
    send_alert "$issues"
    exit 1
fi

log "RESULT: OK"
exit 0
