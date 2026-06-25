# Wonderland ERP — Web Portal UI Plan

**Stack:** Laravel 11 + Inertia.js + Vue 3 + Tailwind (BFF portal)  
**Status:** Implemented — portal Phases 0–8; **S1 Identity & Access admin UI sign-off complete** (checklist Phases 1–7)  
**Gate:** Pilot gate green (UAT 27/27); APIs unchanged; UI is presentation only

PDFs and `specs/traceability/matrix.yaml` remain source of truth for business rules. This folder defines **screens, routes, permissions, and API calls** for the staff portal.

---

## 1. Architecture

### Pattern: single portal + BFF

```text
Browser
  └── http://localhost/              → web-portal (Laravel + Inertia + Vue)
        └── session (encrypted JWT + refresh)
        └── Http:: → wh-gateway → /s1|s2|s3|s4/api/v1/...
```

| Layer | Responsibility | Must NOT do |
|-------|----------------|-------------|
| **web-portal** | Login UX, menus, forms, tables, wizards, PDF download proxy | Payroll math, folio rules, journal posting logic |
| **S1–S4 APIs** | Auth, RBAC, validation, workflows, GL | Render HTML for staff |

Professional modular ERPs (SAP Fiori, Oracle Fusion, etc.) use the same idea: **one shell**, role-based modules, backends stay separate.

### New repo folder

```text
web-portal/
  app/
    Http/Controllers/          # Inertia page controllers (thin)
    Services/Api/              # S1Client, S3Client, … (HTTP to gateway)
    Services/Auth/             # SessionTokenStore, RefreshTokenService
  resources/js/
    Pages/                     # Inertia pages by module
    Components/                # Shared UI (DataTable, MoneyInput, …)
    Layouts/                   # AppLayout, GuestLayout
  routes/web.php
  Dockerfile                   # port 9000
```

Gateway adds:

- `/` → `web-portal:9000` (Inertia pages)
- `/s1/`, `/s2/`, `/s3/`, `/s4/` → unchanged (API only; not called directly from browser JS)

### Docker service (Phase 0)

| Service | Port | Env |
|---------|------|-----|
| `web-portal` | 9000 | `GATEWAY_INTERNAL_URL=http://wh-gateway`, `SESSION_DRIVER=redis` |

`web-portal` calls APIs via **internal** gateway URL (`http://wh-gateway/s3/api/v1/...`), not `localhost`, so it works inside Docker network.

---

## 2. Security model

See [`security.yaml`](security.yaml). Summary:

| Topic | Decision |
|-------|----------|
| Tokens | Store `access_token` + `refresh_token` in **encrypted Laravel session** only |
| Browser | Vue never reads JWT; no `localStorage` tokens |
| API calls | Server-side `Http::withToken()` from BFF clients |
| CSRF | Laravel CSRF on all Inertia forms |
| Permissions | Menu + route middleware from S1 `/auth/me` permissions |
| Authorization | UI hides disallowed actions; **API still enforces** (defense in depth) |
| Session timeout | Align with S1 JWT TTL; refresh server-side before expiry |
| Downloads | PDF/Excel proxied through portal controller (same Bearer session) |

---

## 3. Modules and roles

Navigation is driven by **S1 permissions**, grouped into modules matching hotel operations.

| Module | Primary API | Roles (see `specs/platform/roles.yaml`) |
|--------|-------------|----------------------------------------|
| Dashboard | S4 (+ snapshots S2/S3) | GM, report_viewer, finance_manager |
| Front desk | S3 | receptionist, cashier, GM |
| F&B | S3 | restaurant_manager, cashier |
| Inventory & procurement | S3 | inventory_manager, department_head, finance_manager, GM |
| HR | S2 | hr_manager, department_head |
| Payroll | S2 | payroll_officer, hr_manager, finance_manager |
| Finance | S4 | accountant, finance_manager, GM, report_viewer |
| Admin | S1 | super_admin |

Screen-level detail: [`modules.yaml`](modules.yaml).

---

## 4. Delivery phases

See [`phases.yaml`](phases.yaml). Order is by **hotel operational value**, not system number.

| Phase | Focus | Exit criteria |
|-------|--------|---------------|
| **0** | Scaffold, login, layout, API clients, permission menu | Login works; sidebar reflects role; no business screens yet |
| **1** | Front desk golden path | UAT S3-001, S3-002 reproducible in UI |
| **2** | F&B + folio orders | UAT S3-003, S3-007 |
| **3** | Inventory + PO approval | UAT S3-006, S4-009 |
| **4** | Staff consumption | UAT S3-004 |
| **5** | Group bookings | UAT S3-005 |
| **6** | HR + payroll | UAT S2-001 … S2-006 |
| **7** | Finance + BI | UAT S4-001 … S4-010, E2E-001 |
| **8** | Admin (S1) | Users, roles, audit (super_admin) |

**Phase 0–1 = pilot UI MVP** (reception can run a guest stay without Postman).

---

## 5. UX conventions

| Convention | Rule |
|------------|------|
| Money | ETB, 2 decimals, right-align; display API strings as-is |
| Dates | Hotel local (Africa/Addis_Ababa); ISO in API payloads |
| Lists | Server pagination (`page`, `per_page`); never load full employee/room lists |
| Errors | Map API `{ error: { code, message, details } }` to toast + inline field errors |
| Idempotency | POST that creates side effects sends `Idempotency-Key` (payroll approve, PO approve, journals) |
| Loading | Skeleton on Inertia navigation; disable submit while posting |
| Confirm | Destructive actions (void order, delete draft journal, archive employee) require modal |

---

## 6. Frontend stack detail

| Package | Purpose |
|---------|---------|
| `@inertiajs/vue3` | Page navigation without full SPA boilerplate |
| `vue` 3 | Page components |
| `tailwindcss` | Layout and tokens |
| `@vueuse/core` | Small helpers (debounce search) |
| Optional: chart library | Phase 7 dashboards only |

**Build:** Vite in `web-portal/`; `npm run dev` with volume mount for local HMR.

**Testing (later):**

- Pest feature tests: login, permission gate on routes
- Optional: Playwright for Phase 1 golden path

---

## 7. Traceability

When Phase 0 starts, update `specs/traceability/matrix.yaml`:

```yaml
- key: UI-ALL-001
  status: partial   # → implemented when Phase 8 complete
  evidence: "web-portal Phase N; specs/ui/modules.yaml"
```

Each screen in `modules.yaml` links to UAT scenario keys where applicable.

---

## 8. Out of scope (v1)

- Mobile native apps (API-ready later)
- Offline mode
- WebSockets / live folio push
- Custom report builder
- Multi-property / multi-tenant UI
- Replacing or duplicating S4 PDF dashboards (link or iframe/proxy downloads first)

---

## 9. Implementation checklist (Phase 0)

- [ ] `composer create-project` → `web-portal/` with Inertia + Vue + Tailwind
- [ ] Add `web-portal` service to `docker-compose.yml` (port 9000)
- [ ] Extend `gateway/nginx.conf`: `/` → portal; keep `/s{n}/` APIs
- [ ] `App\Services\Api\GatewayClient` base (token, retry, error parsing)
- [ ] `S1AuthClient`: login, refresh, logout, me
- [ ] Session middleware + `EnsureAuthenticated` for Inertia routes
- [ ] `PermissionMenuBuilder` reading permissions from session
- [ ] Guest layout: Login page
- [ ] App layout: sidebar, header (user, logout), flash toasts
- [ ] Document env in `web-portal/.env.example` (`GATEWAY_INTERNAL_URL`, Redis session)
- [ ] Update root `README.md` with portal URL

Phase 1 checklist: see [`phases.yaml`](phases.yaml) → `phase_1_front_desk`.

---

## 10. References

| Doc | Use |
|-----|-----|
| [`modules.yaml`](modules.yaml) | Screens, routes, permissions, API endpoints |
| [`phases.yaml`](phases.yaml) | Sprint deliverables and UAT mapping |
| [`security.yaml`](security.yaml) | Token and session rules |
| [`../platform/roles.yaml`](../platform/roles.yaml) | Role → module access |
| [`../../postman/`](../../postman/) | Request shapes for BFF clients |
| [`../traceability/matrix.yaml`](../traceability/matrix.yaml) | SDD coverage |
