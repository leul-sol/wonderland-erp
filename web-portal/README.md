# Wonderland ERP — Web Portal

Laravel + Inertia + Vue BFF for staff UI. Business logic stays in S1–S4 APIs.

## Local (Docker)

From repo root after `.\scripts\start.ps1`:

| URL | Purpose |
|-----|---------|
| http://localhost/ | Staff portal (this app) |
| http://localhost/s1/api/v1/... | S1 Identity API (unchanged) |

## Dev credentials

| Item | Value |
|------|--------|
| Username | `super.admin` |
| Password | `SUPER_ADMIN_PASSWORD` in repo root `.env` (default dev fallback: `ChangeMeNow!10` if no `.env`) |
| Service key | `INTERNAL_KEY_CURRENT` in root `.env` (Postman / S2–S4 internal calls) |

On first seed, `super.admin` may have `must_change_password` set (`SEED_ADMIN_MUST_CHANGE_PASSWORD`, default `true`). Change password at **Account → Change password**, or let the smoke script re-sync the account:

```powershell
.\scripts\portal-admin-smoke.ps1   # runs app:sync-super-admin, then admin page smoke
```

Manual reset after UAT lockout/deactivate:

```powershell
docker compose exec s1-identity php artisan app:sync-super-admin
```

### Seeded roles (S1)

Twelve system roles are seeded in S1 and drive portal navigation. Full matrix: [`../specs/platform/roles.yaml`](../specs/platform/roles.yaml).

| Role slug | Typical portal access |
|-----------|------------------------|
| `super_admin` | Full admin — users, roles, permissions, audit |
| `general_manager` | Dashboard, read across modules |
| `finance_manager` | Finance, procurement approval, reports |
| `receptionist` / `cashier` | Front desk, F&B |
| `hr_manager` / `payroll_officer` | HR, payroll |
| `report_viewer` | Read-only dashboards and reports |

Assign roles in **Administration → Users** (requires `S1.identity.users.assign_role`).

## S1 admin UI (sign-off complete)

Portal coverage for S1 Identity & Access:

- Login, session refresh, logout, forced password change
- Users — create, edit, reactivate, reset password, force logout, deactivate, delete, role assign, audit tab
- Roles — create, edit, delete, permission sync
- Permissions — read-only catalog
- Audit log — filter, CSV export

Smoke test:

```powershell
.\scripts\portal-admin-smoke.ps1
```

Hospitality golden path (separate):

```powershell
.\scripts\portal-smoke.ps1
```

## Reset UAT / E2E data

Before another full automated UAT run:

```powershell
.\scripts\reset-uat.ps1
.\scripts\run-uat-e2e.ps1
```

S1 scenarios in E2E: **UAT-S1-001** (login) through **UAT-S1-004** (permission sync audit).

## Dev assets (host)

```bash
cd web-portal
npm install
npm run build   # or npm run dev for HMR
```

Prefer the Docker `web-portal` service for PHP; rebuild assets on the host after Vue changes.

## Tests

```bash
docker compose exec web-portal php artisan test --filter="ChangePasswordTest|AdminPagesTest|AdminActionsTest"
```

## Architecture

See [`../specs/ui/README.md`](../specs/ui/README.md) and [`../specs/ui/security.yaml`](../specs/ui/security.yaml).
