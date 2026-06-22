# S4 — Finance & BI

Laravel 11 application for general ledger, AR/AP, fiscal periods, financial reporting, and operational BI.

## Local setup (Docker)

```powershell
docker compose up -d s4-finance-bi
docker compose exec s4-finance-bi php artisan app:ensure-seeded
curl http://localhost/s4/api/v1/health
```

## Auth

- User JWTs verified via S1 `POST /api/v1/auth/verify`
- S2/S3 journal posts use `X-Service-Key` + `Idempotency-Key`
- S4 reads S2/S3 operational data via service key (cached)

## API surface

### Finance core
- Journals, accounts, fiscal periods, AR/AP (see prior phases)

### Financial reports (`S4.finance.reports.read`)
- `GET /reports/trial-balance`
- `GET /reports/income-statement`
- `GET /reports/balance-sheet`
- `GET /reports/cash-flow`

### Dashboards (`S4.bi.dashboards.read`)
- `GET /dashboards/executive` — finance KPIs
- `GET /dashboards/operations` — finance + hospitality + workforce snapshot

### Operational BI (`S4.bi.reports.read`)
- `GET /bi/reports/revenue-by-source` — posted journal volume by S2/S3/manual
- `GET /bi/reports/hospitality-snapshot` — occupancy, reservations, F&B (from S3)
- `GET /bi/reports/payroll-snapshot` — headcount, payroll runs (from S2)

### Export (`S4.bi.export.create`)
- `POST /bi/exports` — `{ "report": "income_statement", "format": "csv", "fiscal_period_id": 12 }`

### RTM & UAT
- `GET /bi/rtm` — requirements matrix + coverage summary (`S4.bi.rtm.read`)
- `PATCH /bi/rtm/{id}` — update requirement status (`S4.bi.rtm.update`)
- `GET /bi/uat` — UAT scenarios + pass rate (`S4.bi.uat.read`)
- `POST /bi/uat/{id}/results` — record pass/fail (`S4.bi.uat.update`)

Query params: `fiscal_period_id` or `from` + `to` (reports). Filter RTM/UAT with `?system=S3&status=pending`.

## Specs

- Permissions: `../specs/s4/permissions.yaml`
- Chart of accounts: `../specs/s4/coa.yaml`
