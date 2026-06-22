# S4 — Finance & BI

Laravel 11 application. Full spec: `../documents/S4_Finance_BI_SDD.pdf`.

## Local setup (Docker)

From repo root (once `docker-compose` includes the S4 service):

```bash
cp s4-finance-bi/.env.example s4-finance-bi/.env
docker compose up -d --build
docker compose exec s4-finance-bi php artisan key:generate
docker compose exec s4-finance-bi php artisan app:ensure-seeded
curl http://localhost/s4/api/v1/health
```

## Auth

- User JWTs are verified via S1 `POST /api/v1/auth/verify` (`S1AuthService`).
- Service-to-service calls use `X-Service-Key`.
- Journal posts from S2/S3 require `Idempotency-Key` (middleware alias `idempotency`).

## API surface (initial)

- `GET /api/v1/health`

Finance routes (`POST /journal-entries`, COA, reports) are added in follow-up work.

## Specs

- Permissions: `../specs/s4/permissions.yaml`
- Chart of accounts: `../specs/s4/coa.yaml`
