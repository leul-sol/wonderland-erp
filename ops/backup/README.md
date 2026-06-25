# MySQL backup & restore

Wonderland ERP uses one MySQL instance with four databases: `wh_s1_db` … `wh_s4_db`.

## Manual backup (host)

Requires `./backups` mounted on `wh-mysql`. If you added this recently, recreate once:

```powershell
docker compose up -d wh-mysql --force-recreate
```

Then:

```powershell
.\scripts\backup-mysql.ps1
```

Writes `backups/wonderland-mysql-<timestamp>.tar.gz` (git-ignored).

## Manual restore (destructive)

```powershell
.\scripts\restore-mysql.ps1 -Archive backups\wonderland-mysql-20260101T120000Z.tar.gz
```

Prompts for confirmation, then drops and recreates all four databases.

## Restore drill (non-destructive)

Validates the latest backup by importing one dump into a temporary database:

```powershell
.\scripts\restore-drill.ps1
```

Run **monthly** on staging; record the date in your ops log.

## Automated backups (Docker ops profile)

```powershell
docker compose -f docker-compose.yml -f docker-compose.ops.yml --profile ops up -d wh-mysql-backup
```

Environment (root `.env`):

| Variable | Default | Purpose |
|----------|---------|---------|
| `BACKUP_INTERVAL_HOURS` | 24 | Dump interval |
| `BACKUP_RETENTION_DAYS` | 14 | Delete older archives |

Backups are stored in the `wh-mysql-backups` Docker volume. Copy off-host regularly:

```powershell
docker compose -f docker-compose.yml -f docker-compose.ops.yml run --rm wh-mysql-backup sh /opt/backup/backup-loop.sh once
docker cp wonderland-erp-wh-mysql-backup-1:/backups ./backups-from-volume
```

## Schedule (staging / production)

| Task | Frequency | Command |
|------|-----------|---------|
| Backup | Daily | Task Scheduler → `scripts\backup-mysql.ps1` |
| Restore drill | Quarterly | `scripts\restore-drill.ps1` |
| Off-site copy | Daily | Copy `backups\` to object storage |

## Redis / event bus

See [redis-policy.md](redis-policy.md). Redis and `wh-redis-bus` are **ephemeral** — replay from outbox where possible after total loss.
