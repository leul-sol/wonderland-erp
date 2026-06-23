# S2 — Workforce & Payroll

Employees, payroll runs, leave, attendance, and journal posting to S4.

## Dev

```powershell
docker compose up -d s2-workforce s2-workers
docker compose exec s2-workforce php artisan app:ensure-seeded
```

Health: `http://localhost/s2/api/v1/health`

## Golden path

1. Create employee → S1 user provisioned via `wh.events.s2.employee.created`
2. Create payroll run (draft lines for all active employees)
3. Submit payroll → `pending_approval`
4. Approve payroll (requires `Idempotency-Key`) → S4 journal + `approved`
5. Lock payroll run → `locked` (immutable)
6. Event `wh.events.s2.payroll_run.approved` published

## Payroll engine

- ERCA progressive tax brackets (`config/payroll.php`)
- Pension by `pension_category` (`covered` / `not_covered`)
- Overtime from approved `overtime_records` + rate multipliers
- Staff loans (`loan_records`) and S3 meal deductions (`employee_deductions`)

## Leave

- `leave_types` + per-employee `leave_balances` seeded on hire
- `php artisan leave:accrue` — annual leave accrual on service anniversary
- Approving AL/SL/ML sets employee `status` to `on_leave`

## Workers

- `php artisan outbox:publish` — publishes S2 lifecycle events
- `php artisan idempotency:purge` — removes expired idempotency keys
- `php artisan leave:accrue` — annual leave accrual on service anniversary

## HR workflows (SDD §9)

- Departments: `GET/POST/PATCH/DELETE /departments` (`S2.hr.departments.*`)
- Disciplinary: `GET/POST /employees/{id}/disciplinary-records` (suspension → `suspended`, termination → offboarding)
- Assets: `GET/POST /asset-types`, `GET/POST /employees/{id}/assets`, `PUT /assets/{id}/return`
- Guarantors: `POST /employees/{id}/guarantors` (bilingual PDF letter at `.../guarantors/{id}/letter`)
- Payslips: `GET /employees/{id}/payslip/{runId}` (`S2.payroll.payslips.read`) — PDF for approved/locked runs
