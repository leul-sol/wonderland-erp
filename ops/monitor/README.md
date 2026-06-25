# Stack monitoring

Checks API health, failed outbox rows, and Laravel `failed_jobs` across S1–S4 databases.

## One-shot (host — includes worker container status)

```powershell
.\scripts\monitor-stack.ps1
```

Exit code **1** when any check fails (suitable for cron / CI).

## Continuous monitor (Docker ops profile)

```powershell
docker compose -f docker-compose.yml -f docker-compose.ops.yml --profile ops up -d wh-monitor
docker compose logs -f wh-monitor
```

## Alerts

Set in root `.env`:

```env
ALERT_WEBHOOK_URL=https://hooks.slack.com/services/XXX/YYY/ZZZ
FAILED_OUTBOX_THRESHOLD=1
MONITOR_INTERVAL_SECONDS=60
```

Any compatible JSON `{ "text": "..." }` webhook (Slack incoming webhook, Discord, etc.) receives alerts when checks fail.

## What is checked

| Check | Source |
|-------|--------|
| Gateway, S1–S4 `/health`, portal `/up` | HTTP via internal gateway |
| `event_outbox` where `status=failed` | MySQL wh_s1–s4_db |
| `failed_jobs` count | MySQL (if table exists) |
| Worker containers running | Host script only (`monitor-stack.ps1`) |

## Logs

Monitor container writes to volume `wh-monitor-logs` at `/var/log/wh-monitor/check.log`.
