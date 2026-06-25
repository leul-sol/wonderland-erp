# Wonderland ERP — operations

Pilot-ready ops tooling (backup, TLS, monitoring, incident response).

| Area | Docs | Scripts |
|------|------|---------|
| **MySQL backup & restore** | [ops/backup/README.md](backup/README.md) | `scripts/backup-mysql.ps1`, `restore-mysql.ps1`, `restore-drill.ps1` |
| **TLS at gateway** | [ops/tls/README.md](tls/README.md) | `scripts/generate-tls-certs.ps1` + `docker-compose.tls.yml` |
| **Monitoring & alerts** | [ops/monitor/README.md](monitor/README.md) | `scripts/monitor-stack.ps1`, `docker-compose.ops.yml` |
| **Incident response** | [ops/runbooks/incident-response.md](runbooks/incident-response.md) | — |

## Quick commands

```powershell
# Backup now → backups\
.\scripts\backup-mysql.ps1

# Validate latest backup (non-destructive)
.\scripts\restore-drill.ps1

# Health + outbox + workers
.\scripts\monitor-stack.ps1

# Enable HTTPS (self-signed for staging)
.\scripts\generate-tls-certs.ps1
docker compose -f docker-compose.yml -f docker-compose.tls.yml up -d

# Automated backup + continuous monitor
docker compose -f docker-compose.yml -f docker-compose.ops.yml --profile ops up -d
```

Traceability: `specs/traceability/pilot-readiness.yaml`
