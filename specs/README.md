# Wonderland ERP — Executable Specs

Machine-readable contracts extracted from the S0–S4 SDDs. **PDFs in `documents/` remain the narrative source of truth**; these files drive CI, seeders, and contract tests.

| File | Contents |
|------|----------|
| [`platform/events.yaml`](platform/events.yaml) | Cross-system Redis event channels |
| [`platform/cross-system-calls.yaml`](platform/cross-system-calls.yaml) | Sync HTTP between systems |
| [`platform/roles.yaml`](platform/roles.yaml) | 12 platform roles + S0 access matrix |
| [`platform/error-codes.yaml`](platform/error-codes.yaml) | D7 standard error codes |
| [`s1/permissions.yaml`](s1/permissions.yaml) | S1 permission catalog (seed into S1) |

## Traceability & pilot gate

Before UI work or a "production" label:

| File | Purpose |
|------|---------|
| [`traceability/matrix.yaml`](traceability/matrix.yaml) | SDD section → implementation status (no new features until critical gaps reviewed) |
| [`traceability/pilot-readiness.yaml`](traceability/pilot-readiness.yaml) | Backup, monitoring, support checklist (ops — not code) |
| [`../scripts/pilot-gate.ps1`](../scripts/pilot-gate.ps1) | Runs tests + UAT + blocks on critical SDD `missing` |

```powershell
.\scripts\pilot-gate.ps1   # MVP pilot gate — NOT production sign-off
```

## Rules

1. Change cross-system behavior in **S0 narrative + these YAML files** in the same PR.
2. S1 seeds all permissions from `s1/permissions.yaml` plus catalogs from S2–S4 via `CatalogPermissionsSeeder`.
3. Contract tests validate implementations against `cross-system-calls.yaml` and `events.yaml`.

## Known SDD conflicts (track in code comments until errata)

- **AP journal timing:** S0 says goods receipt; S3 §5.3 says PO approval — implemented on goods receipt (S0 + S4).
- **S4 event_outbox:** S0 ID8/ID10 vs S4 §8.1 — follow S0 (S4 consumes events, does not emit on bus for BI).
- **Group bookings:** implemented — `POST /group-bookings` with rooming list; bulk check-in/out via `POST /group-bookings/{id}/check-in`.
