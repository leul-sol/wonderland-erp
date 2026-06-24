# Wonderland ERP — Web Portal (Phase 0)

Laravel + Inertia + Vue BFF shell for staff UI. Business logic stays in S1–S4 APIs.

## Local (Docker)

From repo root after `.\scripts\start.ps1`:

- Portal: http://localhost/
- APIs unchanged: http://localhost/s1/api/v1/...

Login with `super.admin` and `SUPER_ADMIN_PASSWORD` from root `.env`.

## Dev assets (host)

```bash
cd web-portal
npm install
npm run dev
```

Run portal PHP locally only if stack + gateway are up; prefer Docker service `web-portal`.

## Architecture

See [`../specs/ui/README.md`](../specs/ui/README.md).
