# Redis & event bus persistence (BR-002)

| Component | Persistence | Acceptable loss |
|-----------|-------------|-----------------|
| `wh-redis` | In-memory (no AOF/RDB in compose) | Session cache, Laravel cache — users re-login |
| `wh-redis-bus` | In-memory | Unpublished bus messages; **outbox rows in MySQL are source of truth** |

## Policy

1. **Do not** treat Redis bus as durable storage.
2. After Redis bus restart, pending work remains in each service `event_outbox` table (`status=pending`).
3. Workers run `outbox:publish` every 10s — backlog drains automatically if workers are healthy.
4. If outbox rows are `failed`, use monitoring alerts and re-drive manually after fixing root cause.

## Recovery after Redis loss

1. `docker compose restart wh-redis wh-redis-bus s1-workers s2-workers s3-workers s4-workers`
2. Run `.\scripts\monitor-stack.ps1` — confirm zero failed outbox rows
3. If failed rows exist, inspect `event_outbox.last_error` in the relevant DB and replay or fix data

No backup of Redis is required for pilot; MySQL backups cover business data and outbox state.
