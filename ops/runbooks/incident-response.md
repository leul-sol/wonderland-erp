# Incident response runbook (SUP-002)

Wonderland ERP — Docker Compose stack on a single host. **Not production SLOs**; pilot/staging procedures.

## Severity guide

| Level | Example | Target response |
|-------|---------|-----------------|
| **SEV-1** | Portal down, no check-in/folio | 15 min acknowledge, 1 h restore service |
| **SEV-2** | One API (S2/S3/S4) unhealthy | 30 min acknowledge, 4 h restore |
| **SEV-3** | Failed outbox rows, slow portal | Same business day |
| **SEV-4** | UAT failure, non-blocking defect | Next sprint |

## First 5 minutes (any SEV)

1. **Confirm** — open `http://localhost/health` (or `https://` if TLS enabled)
2. **Run monitor** — `.\scripts\monitor-stack.ps1`
3. **Container status** — `docker compose ps`
4. **Note** `X-Request-Id` from browser devtools or API response for log correlation
5. **Communicate** — hotel GM + dev lead (see contact matrix below)

## Symptom → action

### 504 / 502 / portal blank

**Cause:** Portal or API overloaded; single-threaded PHP queue; unhealthy containers.

```powershell
docker compose ps
docker compose restart web-portal wh-gateway
docker compose restart s1-identity s2-workforce s3-hospitality s4-finance-bi
```

Wait 2 minutes, retry. If repeated: `docker compose down` then `docker compose up -d` (data preserved in volumes).

See also: Windows Docker slowness — move repo to WSL2 for dev.

### 503 SERVICE_UNAVAILABLE on `/s1/` … `/s4/`

**Cause:** Named service not healthy.

```powershell
docker compose logs s1-identity --tail 50
docker compose exec s1-identity php artisan app:ensure-seeded
curl http://localhost/s1/api/v1/health
```

Replace `s1-identity` with the failing service (`s2-workforce`, `s3-hospitality`, `s4-finance-bi`).

### Login: Account temporarily locked

**Cause:** 5 failed password attempts (30 min lockout).

```powershell
docker compose exec -T s1-identity php artisan tinker --execute="App\Models\User::query()->where('username', 'super.admin')->update(['failed_login_count' => 0, 'locked_until' => null]);"
```

Use password from root `.env` → `SUPER_ADMIN_PASSWORD`.

### Login: Account is deactivated

**Cause:** User row missing or `is_active=false`; stale JWT.

```powershell
docker compose exec -T s1-identity php artisan app:ensure-seeded
```

Log in again (fresh session).

### Monitor: failed outbox rows

**Cause:** Downstream API rejected event; worker will not retry indefinitely.

```powershell
docker compose exec -T wh-mysql mysql -uroot -p<MYSQL_ROOT_PASSWORD> -e `
  "SELECT id, event_type, status, last_error FROM wh_s3_db.event_outbox WHERE status='failed' ORDER BY id DESC LIMIT 10;"
```

1. Fix root cause (validation, missing master data, S4 down)
2. After fix, set row back to `pending` **only** if ops understands replay impact:
   ```sql
   UPDATE wh_s3_db.event_outbox SET status='pending', attempts=0 WHERE id=<id>;
   ```
3. Confirm workers running: `docker compose ps s3-workers`

### Worker container exited

```powershell
docker compose up -d s1-workers s2-workers s3-workers s4-workers
docker compose logs s1-workers --tail 30
```

Workers auto-restart in compose command loops; exit usually means DB/Redis unreachable.

### MySQL data corruption / bad migration

**Last resort — restore from backup:**

```powershell
.\scripts\restore-mysql.ps1 -Archive backups\wonderland-mysql-<timestamp>.tar.gz
docker compose restart s1-identity s2-workforce s3-hospitality s4-finance-bi web-portal
```

Always run `.\scripts\restore-drill.ps1` monthly so backups are known-good.

### Redis / bus restart

Ephemeral by design. See [ops/backup/redis-policy.md](../backup/redis-policy.md).

```powershell
docker compose restart wh-redis wh-redis-bus s1-workers s2-workers s3-workers s4-workers
```

## Contact matrix (fill in for hotel pilot)

| Area | Primary | Escalation |
|------|---------|------------|
| Portal / login | Dev team | Super admin |
| Front desk / S3 | Front office manager | Dev team |
| HR / payroll / S2 | HR manager | Finance |
| Finance / S4 | Finance manager | GM |
| Infrastructure | Ops / IT | Dev team |

## Escalation

- **Failed UAT** (`.\scripts\run-uat-e2e.ps1`) — dev fixes before pilot sign-off; do not mark production
- **Fiscal period close blocked** — finance manager + dev; never force-close DB without S4 owner

## Post-incident

1. Record: time, symptom, root cause, fix, `request_id`s
2. Update this runbook if steps were wrong or missing
3. If data loss: verify backup + drill dates in ops log

## Related docs

- [ops/README.md](../README.md)
- [ops/backup/README.md](../backup/README.md)
- [ops/monitor/README.md](../monitor/README.md)
- [ops/tls/README.md](../tls/README.md)
- `scripts/start.ps1`, `scripts/reset-uat.ps1`
