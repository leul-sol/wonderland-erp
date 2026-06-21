# Wonderland Hotel ERP

Modular hotel ERP for Wonderland Hotel — four Laravel 11 systems behind a single API gateway.

| System | Path | Port (internal) | Database |
|--------|------|-----------------|----------|
| S1 Identity & Access | `s1-identity-access/` | 9001 | `wh_s1_db` |
| S2 Workforce & Payroll | `s2-workforce-payroll/` | 9002 | `wh_s2_db` |
| S3 Hospitality Operations | `s3-hospitality-operations/` | 9003 | `wh_s3_db` |
| S4 Finance & BI | `s4-finance-bi/` | 9004 | `wh_s4_db` |

Design source: [`documents/`](documents/) (S0–S4 SDDs, v1.0). Executable contracts: [`specs/`](specs/).

## Quick start

```bash
# Copy env templates (first time)
cp s1-identity-access/.env.example s1-identity-access/.env

# Start platform (gateway + mysql + redis + S1)
docker compose up -d --build

# Run S1 migrations (inside container)
docker compose exec s1-identity php artisan migrate --seed

# Health checks
curl http://localhost/s1/api/v1/health
```

External API base: `http://localhost/s{n}/api/v1/...`

## Build order

1. **S1** — auth, JWT, RBAC, audit (current phase)
2. **S4 Finance** — chart of accounts, `POST /journal-entries`
3. **S3 golden path** — check-in → F&B on folio → settle → GL
4. **S2** — HR → Leave → Payroll
5. **S4 BI** — dashboards, reports, RTM/UAT

Read [`specs/README.md`](specs/README.md) before changing cross-system contracts.

## Documentation

| Doc | Purpose |
|-----|---------|
| S0 | Platform integration — read first |
| S1–S4 | Per-system build specifications |
