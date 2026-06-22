# S2 — Workforce & Payroll

Employees, payroll runs, and journal posting to S4.

## Dev

```powershell
docker compose up -d s2-workforce s2-workers
docker compose exec s2-workforce php artisan app:ensure-seeded
```

Health: `http://localhost/s2/api/v1/health`

## Golden path

1. Create employee → S1 user provisioned via `wh.events.s2.employee.created`
2. Create payroll run (draft lines for all active employees)
3. Approve payroll → S4 journal (salaries, pension, tax)
4. Event `wh.events.s2.payroll_run.approved` published
