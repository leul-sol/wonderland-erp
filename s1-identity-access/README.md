# S1 — Identity & Access Platform

Laravel 11 application. Full spec: `../documents/S1_Identity_Access_Platform_SDD.pdf`.

## Local setup (Docker)

From repo root:

```bash
cp s1-identity-access/.env.example s1-identity-access/.env
docker compose up -d --build
docker compose exec s1-identity php artisan key:generate
docker compose exec s1-identity php artisan app:ensure-seeded
curl http://localhost/s1/api/v1/health
```

## Seeded super admin (dev only)

| Field | Value |
|-------|-------|
| Username | `super.admin` |
| Password | `ChangeMeNow!10` |

Change immediately after first login.

## API surface

### Auth
- `POST /api/v1/auth/login`, `refresh`, `verify`, `logout`, `change-password`
- `GET /api/v1/auth/me`, `jwks`

### Admin (JWT + permission, or service key for GET reads)
- `GET/POST/PATCH/DELETE /api/v1/users`
- `POST /api/v1/users/{id}/deactivate|force-logout|reset-password`
- `PUT /api/v1/users/{id}/roles`
- `GET/POST/PATCH/DELETE /api/v1/roles`
- `PUT /api/v1/roles/{id}/permissions`
- `GET /api/v1/permissions`
- `GET /api/v1/audit-logs`

- `DELETE /api/v1/users/{id}/roles/{rid}`
- `GET /api/v1/permissions/{domain}`
- `GET /api/v1/audit-logs/user/{id}`
- `GET /api/v1/openapi.json`

S4 reads `users`, `roles`, and `audit-logs` via `X-Service-Key`.

### Events & workers
- `php artisan events:consume-s2` — S2 employee lifecycle → user provision/sync/deactivate
- `php artisan outbox:publish` — publishes `wh.events.s1.permission.changed`
- Docker service `s1-workers` runs both in the background

## Remaining (cross-system / ops)

1. PHPStan in CI (baseline config at repo root when enabled)
2. Production DR runbook (D14)

Permission catalogs for S1–S4 are loaded from `../specs/*/permissions.yaml` via `CatalogPermissionsSeeder`.

Contract smoke tests: `tests/Contract/PlatformContractTest.php`.
