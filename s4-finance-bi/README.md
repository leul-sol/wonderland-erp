# S4 — Finance & BI

Laravel 11 application for general ledger, AR/AP, fiscal periods, and financial reporting.

## Local setup (Docker)

```powershell
docker compose up -d s4-finance-bi
docker compose exec s4-finance-bi php artisan app:ensure-seeded
curl http://localhost/s4/api/v1/health
```

## Auth

- User JWTs verified via S1 `POST /api/v1/auth/verify`
- S2/S3 journal posts use `X-Service-Key` + `Idempotency-Key`

## API surface

### Finance core
- `POST /journal-entries` — create journal (S2/S3 auto-post; manual → draft)
- `POST /journal-entries/{id}/approve` | `/post` | `/reverse`
- `GET /accounts`, `GET /fiscal-periods`, `POST /fiscal-periods/{id}/close|lock`
- `GET /receivables`, `POST /receivables/{id}/settle`
- `GET /payables`, `POST /payables/{id}/settle`

### Reports (Phase 3)
- `GET /reports/trial-balance?fiscal_period_id=` or `?from=&to=`
- `GET /reports/income-statement`
- `GET /reports/balance-sheet`
- `GET /dashboards/executive` — revenue, expenses, net income, cash, AR/AP KPIs

Reports aggregate **posted** journal lines only.

## Specs

- Permissions: `../specs/s4/permissions.yaml`
- Chart of accounts: `../specs/s4/coa.yaml`
