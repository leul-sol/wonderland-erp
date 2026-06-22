# Wonderland Hotel ERP

Modular hotel ERP for Wonderland Hotel — four Laravel 11 systems behind a single API gateway.

| System | Path | Port (internal) | Database |
|--------|------|-----------------|----------|
| S1 Identity & Access | `s1-identity-access/` | 9001 | `wh_s1_db` |
| S2 Workforce & Payroll | `s2-workforce-payroll/` | 9002 | `wh_s2_db` |
| S3 Hospitality Operations | `s3-hospitality-operations/` | 9003 | `wh_s3_db` |
| S4 Finance & BI | `s4-finance-bi/` | 9004 | `wh_s4_db` |

Design source: [`documents/`](documents/) (S0–S4 SDDs, v1.0). Executable contracts: [`specs/`](specs/).

## Platform status

All four systems are implemented and wired through the gateway:

- **S1** — JWT auth, RBAC (79 permissions), users/roles, audit, cross-system permission sync
- **S2** — employees, payroll (with deductions), leave, attendance, severance, outbox events
- **S3** — reservations, folios, F&B, procurement, staff consumption → S2 deductions
- **S4** — GL, fiscal periods, budgets, 24-report BI catalog, dashboards, RTM/UAT tracking, event consumers

External API base: `http://localhost/s{n}/api/v1/...`

## Quick start

```powershell
# From repo root (Windows)
.\scripts\start.ps1
```

`start.ps1` copies per-service `.env` files on first run, builds containers, runs migrations, and calls `app:ensure-seeded` on S1–S4.

Default dev login: `super.admin` / `ChangeMeNow!10` (must change password on first login).

```bash
# Manual health checks
curl http://localhost/s1/api/v1/health
curl http://localhost/s2/api/v1/health
curl http://localhost/s3/api/v1/health
curl http://localhost/s4/api/v1/health
```

## Automated UAT / E2E

After the stack is up and seeded:

```powershell
.\scripts\run-uat-e2e.ps1
```

The script walks the hotel golden path (S3 check-in → folio → settle), payroll with staff-meal deductions (S2), finance reports (S4), and records UAT results via `POST /s4/api/v1/bi/uat/{id}/results`. Check outcomes with `GET /s4/api/v1/bi/uat` (RTM links should verify when scenarios pass).

## Secrets (staging / shared hosts)

**Before any shared host**, copy the root template and rotate every value:

```powershell
copy .env.example .env
# Edit .env — set INTERNAL_KEY_CURRENT, MYSQL_ROOT_PASSWORD, DB_PASSWORD, SUPER_ADMIN_PASSWORD
docker compose up -d --build
```

Docker Compose reads `.env` at the repo root for variable substitution. Without `.env`, local dev fallbacks apply (`dev-internal-key-change-in-prod`, `ChangeMeNow!10`, etc.). Never commit `.env`.

Per-service `.env.example` files mirror `INTERNAL_KEY_CURRENT` and `DB_PASSWORD` for running artisan outside Docker.

## Testing

```bash
docker compose exec s1-identity php artisan test
docker compose exec s2-workforce php artisan test
docker compose exec s3-hospitality php artisan test
docker compose exec s4-finance-bi php artisan test
```

Postman collections: [`postman/`](postman/).

## Documentation

| Doc | Purpose |
|-----|---------|
| [`specs/README.md`](specs/README.md) | Cross-system contracts — read before API changes |
| S0 | Platform integration |
| S1–S4 | Per-system build specifications |
