#!/bin/sh
set -eu

interval="${MONITOR_INTERVAL_SECONDS:-60}"

echo "[monitor] Wonderland ERP stack monitor (interval ${interval}s)"
echo "[monitor] webhook: ${ALERT_WEBHOOK_URL:-disabled}"

while true; do
    /opt/monitor/check-stack.sh || true
    sleep "$interval"
done
