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
| Password | `SUPER_ADMIN_PASSWORD` in repo root `.env` (see root `.env.example`) |

First login may require a password change when `SEED_ADMIN_MUST_CHANGE_PASSWORD=true` (default).

After UAT lockout or deactivation, re-sync dev admin:

```bash
docker compose exec s1-identity php artisan app:sync-super-admin
```

## Portal integration (sign-off)

Staff admin UI is implemented in `../web-portal/` (Inertia + Vue BFF):

- Users, roles, permissions catalog, audit log (portal proxy to this API)
- Automated smoke: `../scripts/portal-admin-smoke.ps1`
- Portal tests: `docker compose exec web-portal php artisan test --filter=Admin`

S1 backend + portal admin checklist **Phases 1–7 complete** — no further S1 portal work unless the SDD revision changes.

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

## Ops runbooks

- **JWKS rotation:** `../ops/runbooks/jwks-rotation.md`
- **Incident response:** `../ops/runbooks/incident-response.md`
- **DR restore drill:** `../scripts/restore-drill.ps1` (logs `dr.restore_drill` to audit on success)

## Remaining (cross-system / ops)

1. PHPStan in CI (baseline config at repo root when enabled)
2. Dual-key JWKS for seamless rotation (single key today)

Permission catalogs for S1–S4 are loaded from `../specs/*/permissions.yaml` via `CatalogPermissionsSeeder`.

Contract smoke tests: `tests/Contract/PlatformContractTest.php`.
